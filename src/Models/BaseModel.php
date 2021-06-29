<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RushApp\Core\Enums\ModelRequestParameters;

abstract class BaseModel extends Model
{
    use BaseModelTrait;

    protected $hidden = [
        'current_translation',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($this->modelTranslationClass && request()->get(ModelRequestParameters::WITH)) {
            $this->with[] = 'current_translation';
        }
    }

    public function toArray()
    {
        $data = parent::toArray();
        $translationFields = [];
        if ($this->modelTranslationClass && $this->current_translation) {
            $translationFields = Arr::except($this->current_translation->toArray(), [
                'id',
                'language_id',
                'created_at',
                'updated_at',
                $this->current_translation()->getForeignKeyName(),
            ]);
        }

        return array_merge($data, $translationFields);
    }
}
