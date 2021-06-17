<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RushApp\Core\Enums\ModelRequestParameters;
use RushApp\Core\Services\UserActionsService;

abstract class BaseModel extends Model
{
    use BaseModelTrait;

    protected UserActionsService $userActionsService;
    
    protected $hidden = [
        'current_translation',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($this->modelTranslationClass && request()->get(ModelRequestParameters::WITH)) {
            $this->with[] = 'current_translation';
        }

        $this->userActionsService = resolve(UserActionsService::class);
    }

    public function canIndex(): bool
    {
        return $this->userActionsService
            ->getUserActions()
            ->where('action_name', 'index')
            ->where('entity_name', $this->getTable())
            ->isNotEmpty();
    }

    public function isOwner(): bool
    {
        return $this->userActionsService
            ->getUserActions()
            ->where('is_owner', true)
            ->where('action_name', 'index')
            ->where('entity_name', $this->getTable())
            ->isNotEmpty();
    }

    public function canShow(): bool
    {
        return $this->canPerformAction('show');
    }

    public function canStore(): bool
    {
        return $this->userActionsService
            ->getUserActions()
            ->where('action_name', 'store')
            ->where('entity_name', $this->getTable())
            ->isNotEmpty();
    }

    public function canUpdate(): bool
    {
        return $this->canPerformAction('update');
    }

    public function canDestroy(): bool
    {
        return $this->canPerformAction('destroy');
    }

    protected function canPerformAction(string $actionName): bool
    {
        $roleActions = $this->userActionsService
            ->getUserActions()
            ->where('action_name', $actionName)
            ->where('entity_name', $this->getTable());

        if ($canPerformAction = $roleActions->isNotEmpty()) {
            return $this->checkOnOwner($roleActions);
        }

        return false;
    }

    protected function checkOnOwner(Collection $roleActions): bool
    {
        $needCheckOnOwner = $roleActions->where('is_owner', true)->isNotEmpty();
        if ($needCheckOnOwner) {
            return auth()->id() === $this->user_id;
        }

        return true;
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
