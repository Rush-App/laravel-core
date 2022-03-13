<?php

namespace RushApp\Core\Models;

use App\Models\Post\PostTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RushApp\Core\Enums\ModelRequestParameters;
use RushApp\Core\Services\UserActionsService;

trait CoreBaseModelTrait
{
    /**
     * controller model name
     * @var string
     */
    private string $modelClass;

    /**
     * You should always use these model names.
     * translation table name (example: CountryTranslation)
     * @var string
     */
    private ?string $modelTranslationClass;

    /**
     * table singular name in the database (example: model - Country, $tableSingularName - country)
     * @var string
     */
    private string $tableSingularName;

    /**
     * table plural name in the database (example: model - Country, $tablePluralName - countries)
     * @var string
     */
    private string $tablePluralName;

    /**
     * table translation name for $modelTranslationClass (example: table - countries, $tableTranslationName - country_translations)
     * @var string
     */
    private string $tableTranslationName;

    /** @return string */
    public function getTableSingularName(): string
    {
        return $this->tableSingularName;
    }

    /**
     * add translation data
     * @return HasMany
     */
    public function translations(): HasMany
    {
        return $this->hasMany(
            $this->modelTranslationClass,
            $this->getNameForeignKeyForTranslationTable(),
            $this->getKeyName()
        );
    }

    /** @return HasOne */
    public function current_translation(): HasOne
    {
        return $this->hasOne($this->modelTranslationClass)->where('language_id', request()->get('language_id'));
    }

    /**
     * set the initial parameters from the name of the model received from the controller
     * (the name of the model must be indicated in each controller)
     */
    protected function initializeCoreBaseModelTrait(): void
    {
        $this->modelClass = static::class;
        $this->modelTranslationClass = class_exists($this->modelClass.'Translation') ? $this->modelClass.'Translation': null;

        $this->tablePluralName = $this->modelClass::getTable();
        $this->tableSingularName = Str::singular($this->tablePluralName);
        $this->tableTranslationName = $this->getTableSingularName().'_translations';
    }

    /** @return string */
    private function getTablePluralName(): string
    {
        return $this->tablePluralName;
    }

    /** @return string */
    private function getTranslationTableName(): string
    {
        return $this->tableTranslationName;
    }

    /**
     * @param int $id - ID record to get
     * @return mixed
     */
    private function getOneRecord(int $id)
    {
        return $this->modelClass::find($id);
    }

    /**
     * Example:
     * - Main table name: base_invoices
     * - Translation table name: base_invoice_translation
     * - In translation table: $table->foreign('base_invoice_id')->references('id')->on('base_invoices');
     *
     * @return string - Like this: base_invoice_id
     */
    private function getNameForeignKeyForTranslationTable(): string
    {
        return $this->getTableSingularName().'_id' ;
    }

    /**
     * Updates the model and then returns it
     *
     * @param Model $model - specific record from the database
     * @param array $dataToUpdate - data for updating
     * @return array
     */
    private function updateOneRecord(Model $model, array $dataToUpdate)
    {
        /** @var Model|static $mainModel */
        $mainModel = tap($model)->update($dataToUpdate);
        $modelAttributes = $mainModel->getAttributes();

        if ($this->isTranslatable()) {
            /** @var Model $translationModel */
            $translationModel = $mainModel->translations()->firstOrNew([
                'language_id' => $dataToUpdate['language_id']
            ]);
            $translationModel->update($dataToUpdate);

            $modelAttributes = array_merge($translationModel->getAttributes(), $modelAttributes);
        }
        return $modelAttributes;
    }

    /**
     * Checking is Main table has Translation table and correct foreignKeyName
     *
     * @return bool
     */
    private function isTranslatable(): bool
    {
        return !is_null($this->modelTranslationClass);
    }

    /**
     * Check if this record matches this user
     *
     * @param self $model - specific record from the database
     * @param int $ownerId - column value to check whether a record matches a specific user
     * @return bool
     */
    private function canDoActionWithModel(self $model, int $ownerId): bool
    {
        $userActionsService = resolve(UserActionsService::class);
        if ($userActionsService->canUserPerformAction()) {
            return true;
        }
        $columnName = $model->getOwnerKey();

        return $this->isColumnExistInTable($columnName, $this->getTablePluralName())
            ? $model->{$columnName} === $ownerId
            : false;
    }

    /**
     * Removes array elements by nonexistent columns in the table
     *
     * @param array $params - column names in the table (use for filter 'where')
     * @return array
     */
    private function filteringForParams(array $params): array
    {
        return array_filter($params, fn($v, $k) => (
            $this->isColumnExistInTable($k, $this->getTablePluralName()) || $this->isColumnExistInTable($k, $this->getTranslationTableName())
        ), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * checking for the existence of column names in the table
     * @param string $columnName - column name in the table
     * @param string $tableName
     * @return bool
     */
    private function isColumnExistInTable(string $columnName, string $tableName): bool
    {
       return $this->getTableColumns($tableName)->contains($columnName);
    }

    /**
     * @param string $tableName
     * @return Collection
     */
    private function getTableColumns(string $tableName): Collection
    {
        return Cache::remember(
            "$tableName-columns",
            config('rushapp_core.default_cache_ttl'),
            fn () => collect(DB::getSchemaBuilder()->getColumnListing($tableName))
        );
    }

    /**
     * Getting a list of values as a string.
     * Convert it to an array for substitution to query
     * Example: http://127.0.0.1:8000/test?selected_fields=year,recipient_company,id&order_by=year,recipient_company,id
     *
     * @param string $fields
     * @return array
     */
    private function getValueForExistingTableColumns (string $fields): array
    {
        $resultArrForSelect = [];
        $modelTranslationClassExist = class_exists($this->modelTranslationClass);

        $selectedFields = explode(",", $fields);
        foreach ($selectedFields as $selectedField) {

            //To avoid duplicate id in different tables. Using the ID of the main table
            if (($modelTranslationClassExist && $selectedField !== 'id') || !$modelTranslationClassExist) {
                if ($this->isColumnExistInTable($selectedField, $this->getTranslationTableName())) {
                    array_push($resultArrForSelect, $selectedField);
                }
                if ($this->isColumnExistInTable($selectedField, $this->getTablePluralName())) {
                    array_push($resultArrForSelect, $selectedField);
                }
            } else {
                array_push($resultArrForSelect, $this->getTablePluralName().'.id');
            }
        }

        return $resultArrForSelect;
    }

    /**
     * @param array $fields
     * @param string $tableName
     * @return array
     */
    private function filterExistingColumnsInTable(array $fields, string $tableName): array
    {
        $filteredFields = [];
        foreach ($fields as $field) {
            if ($this->isColumnExistInTable($field, $tableName)) {
                $filteredFields[] = $field;
            }
        }
        return $filteredFields;
    }

    /**
     * Example: http://127.0.0.1:8000/test?where_in=year:2018,2014,2020|user_id:2,2,5,6
     * @param string $parametersString
     * @param bool $shouldCheckColumnsInTable
     * @return Collection
     */
    protected function parseParameterWithAdditionalValues(string $parametersString, bool $shouldCheckColumnsInTable = true): Collection
    {
        $parameters = explode('|', $parametersString);
        $parsedParameters = collect();
        foreach ($parameters as $parameter) {
            if (str_contains($parameter, ':')) {
                [$parameterName, $parameterValues] = explode(':', $parameter);

                if ($shouldCheckColumnsInTable && !$this->getValueForExistingTableColumns($parameterName)) {
                    continue;
                }

                $parsedParameters->add([
                    'name' => $parameterName,
                    'values' => explode(',', $parameterValues),
                ]);
            } else {
                $parsedParameters->add(['name' => $parameter]);
            }
        }
        return $parsedParameters;
    }
}
