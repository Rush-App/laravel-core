<?php

namespace RushApp\Core\Models;

use App\Models\Post\Post;
use App\Models\Post\PostTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RushApp\Core\Enums\ModelRequestParameters;
use RushApp\Core\Exceptions\CoreHttpException;
use RushApp\Core\Services\LoggingService;

trait BaseModelTrait
{
    use CoreBaseModelTrait;

    /**
     * Return a collections of one or more records with or without translation
     *
     * @param array $requestParameters
     * @param array $withRelationNames
     * @return Builder
     */
    public function getQueryBuilder(array $requestParameters, array $withRelationNames): Builder
    {
        //checking for the issuance of data that belongs to the current user
        if ($this->isOwner()) {
            $requestParameters['user_id'] = Auth::id();
        }

        /** @var Builder $query */
        $query = (new $this->modelClass)->query();

        if ($this->modelTranslationClass) {
            $query->addSelect($this->getTranslationTableName().'.*')
                ->leftJoin($this->getTranslationTableName(), $this->getTranslationTableName().'.'.$this->getNameForeignKeyForTranslationTable(), '=', $this->tablePluralName.'.id')
                ->where('language_id', request()->get('language_id'));
        }

        //adding data from main table
        // Do not change position. Need to be after all additional selections
        $query->addSelect($this->tablePluralName.'.*');

        $this->addQueryOptions($query, $requestParameters);
        $this->addWithData($query, $requestParameters, $withRelationNames);

        return $query;
    }

    public function getQueryBuilderOne(array $requestParameters, int $entityId, array $withRelationNames): Builder
    {
        $query = $this->getQueryBuilder($requestParameters, $withRelationNames);

        return $query->where($this->getTable().'.id', $entityId);
    }

    protected function addWithData(Builder $query, array $requestParameters, array $withRelationNames)
    {
        if (!empty($requestParameters[ModelRequestParameters::WITH])) {
            $requestedWithParameters = $this->parseParameterWithAdditionalValues($requestParameters[ModelRequestParameters::WITH], false);

            $withRelations = [];
            foreach ($requestedWithParameters as $withParameter) {
                if (in_array($withParameter['name'], $withRelationNames) && method_exists($this, $withParameter['name'])) {
                    $withRelations[$withParameter['name']] = function ($q) use ($withParameter, $requestParameters) {
                        // TODO: Fix hasMany relation in "with" query
                        if (isset($withParameter['values'])) {
                            $tableName = Str::plural($withParameter['name']);
                            $values = $this->filterExistingColumnsInTable($withParameter['values'], $tableName);

                            $relationsFields = array_map(fn ($value) => $tableName.'.'.$value, $values);
                            $relationsFields ? $q->select($relationsFields) : $q->select('*');
                        } else {
                            $q->select('*');
                        }
                    };
                }
            }

            foreach (array_keys($withRelations) as $relationName) {
                $withRelation = $this->modelClass::$relationName();
                if ($withRelation instanceof BelongsTo) {
                    $query->addSelect($withRelation->getForeignKeyName());
                }
            }

            $query->with($withRelations);
        }
    }

    protected function addQueryOptions(Builder $query, array $requestParameters)
    {
        //Select only this data
        if(!empty($requestParameters[ModelRequestParameters::SELECTED_FIELDS])
            &&!empty($select = $this->getValueForExistingTableColumns($requestParameters[ModelRequestParameters::SELECTED_FIELDS]))
        ) {
            $query->select($select);
        }

        //Sort by a given field
        if(!empty($requestParameters[ModelRequestParameters::ORDER_BY_FIELD])) {
            $parsedOrderParameters = $this->parseParameterWithAdditionalValues($requestParameters[ModelRequestParameters::ORDER_BY_FIELD]);
            if ($parsedOrderParameters->isNotEmpty()) {
                foreach ($parsedOrderParameters as $parsedOrderParameter) {
                    $query->orderBy($parsedOrderParameter['name'], $parsedOrderParameter['values'][0] ?? 'asc');
                }
            }
        }

        //give data where some field is NotNull
        if (
            !empty($requestParameters[ModelRequestParameters::WHERE_NOT_NULL]) &&
            !empty($whereNotNull = $this->getValueForExistingTableColumns($requestParameters[ModelRequestParameters::WHERE_NOT_NULL]))
        ) {
            $query->whereNotNull($whereNotNull);
        }

        //give data where some field is Null
        if (
            !empty($requestParameters[ModelRequestParameters::WHERE_NULL]) &&
            !empty($whereNull = $this->getValueForExistingTableColumns($requestParameters[ModelRequestParameters::WHERE_NULL]))
        ) {
            $query->whereNull($whereNull);
        }

        if (!empty($requestParameters[ModelRequestParameters::WHERE_BETWEEN])) {
            $parsedParameters = $this->parseParameterWithAdditionalValues($requestParameters[ModelRequestParameters::WHERE_BETWEEN]);
            if ($parsedParameters->isNotEmpty()) {
                foreach ($parsedParameters as $parsedParameter) {
                    $query->whereBetween($parsedParameter['name'], $parsedParameter['values']);
                }
            }
        }

        if (!empty($requestParameters[ModelRequestParameters::WHERE_IN])) {
            $parsedParameters = $this->parseParameterWithAdditionalValues($requestParameters[ModelRequestParameters::WHERE_IN]);
            if ($parsedParameters->isNotEmpty()) {
                foreach ($parsedParameters as $parsedParameter) {
                    $query->whereIn($parsedParameter['name'], $parsedParameter['values']);
                }
            }
        }

        if (!empty($requestParameters[ModelRequestParameters::WHERE_NOT_IN])) {
            $parsedParameters = $this->parseParameterWithAdditionalValues($requestParameters[ModelRequestParameters::WHERE_NOT_IN]);
            if ($parsedParameters->isNotEmpty()) {
                foreach ($parsedParameters as $parsedParameter) {
                    $query->whereNotIn($parsedParameter['name'], $parsedParameter['values']);
                }
            }
        }

        //Get limited data
        if(!empty($requestParameters[ModelRequestParameters::LIMIT])) {
            $query->limit($requestParameters[ModelRequestParameters::LIMIT]);
        }

        if(!empty($requestParameters[ModelRequestParameters::OFFSET])) {
            $query->offset($requestParameters[ModelRequestParameters::OFFSET]);
        }

        //Parameters for "where", under what conditions the request will be displayed
        $rawWhereParams = Arr::except($this->filteringForParams($requestParameters), ['language_id']);
        foreach ($rawWhereParams as $name => $value) {
            if (preg_match('/^(<>|<|>|<\=|>\=|like)\|/', $value, $matched)) {
                $operator = trim($matched[0], '|');
                $query->where($name, $operator, str_replace("$operator|", '', $value));
            } else {
                $query->where($name, $value);
            }
        }
    }

    /**
     * Creating a new record in the database
     * Return the created record
     *
     * @param array $requestParameters
     * @return array
     */
    public function createOne(array $requestParameters): array
    {
        $requestParameters['user_id'] = Auth::id();

        try {
            /** @var Model|static $mainModel */
            $mainModel = $this->modelClass::create($requestParameters);

            $modelAttributes = $mainModel->getAttributes();
            if ($this->isTranslatable()) {
                $translationModel = $mainModel->translations()->create($requestParameters);
                $modelAttributes = array_merge($translationModel->getAttributes(), $modelAttributes);
            }

            return $modelAttributes;
        } catch (\Exception $e) {
            LoggingService::critical('Model creation error - '.$e->getMessage());
            throw new CoreHttpException(409, __('core::error_messages.save_error'));
        }
    }

    /**
     * Updates the model and then returns it (with checking for compliance of the record to the user)
     *
     * @param array $requestParameters
     * @param int $entityId
     * @param string $columnName - column name to check whether a record matches a specific user
     * @param $valueForColumnName - column value to check whether a record matches a specific user
     * @return array
     */
    public function updateOne(array $requestParameters, int $entityId, $valueForColumnName, string $columnName = 'user_id'): array
    {
        $model = $this->getOneRecord($entityId);
        if (!$model) {
            LoggingService::notice('Cannot update. Model '.static::class.' '.$entityId.' not found');
            throw new CoreHttpException(404, __('core::error_messages.not_found'));
        }

        if (!$this->canDoActionWithModel($model, $columnName, $valueForColumnName)) {
            LoggingService::notice('Cannot update. Permission denied for model'.static::class.' '.$entityId);
            throw new CoreHttpException(403, __('core::error_messages.permission_denied'));
        }

        return $this->updateOneRecord($model, $requestParameters);
    }


    /**
     * Delete one record with checking for compliance of the record to the user
     *
     * @param string $columnName - column name to check whether a record matches a specific user
     * @param $valueForColumnName - column value to check whether a record matches a specific user
     * @param int $entityId
     * @return void
     */
    public function deleteOne(int $entityId, $valueForColumnName, string $columnName = 'user_id'): void
    {
        /** @var Model $model */
        $model = $this->getOneRecord($entityId);
        if (!$model) {
            LoggingService::notice('Cannot delete. Model '.static::class.' '.$entityId.' not found');
            throw new CoreHttpException(404, __('core::error_messages.not_found'));
        }

        if (!$this->canDoActionWithModel($model, $columnName, $valueForColumnName)) {
            LoggingService::notice('Cannot delete. Permission denied for model'.static::class.' '.$entityId);
            throw new CoreHttpException(403, __('core::error_messages.permission_denied'));
        }

        try {
            $model->delete();
        } catch (\Exception $e) {
            LoggingService::critical('Cannot delete. Model '.static::class.' '.$entityId.' '.$e->getMessage());
            throw new CoreHttpException(409, __('core::error_messages.destroy_error'));
        }
    }
}