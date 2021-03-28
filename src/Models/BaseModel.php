<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RushApp\Core\Services\UserActionsService;

abstract class BaseModel extends Model
{
    use BaseModelTrait;

    protected UserActionsService $userActionsService;

    public function __construct(array $attributes = [])
    {
        $this->initBaseModel();
        parent::__construct($attributes);

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
}
