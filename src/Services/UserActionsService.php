<?php

namespace RushApp\Core\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RushApp\Core\Models\Action;
use RushApp\Core\Models\BaseModel;

class UserActionsService
{
    private ?string $actionName;
    private Collection $actions;

    public function __construct(private Request $request) {
        $this->actionName = $this->request->route()->getName();
        $this->actions = $this->getUserActions();
    }

    public function canUserPerformAction(): bool
    {
        $controller = $this->request->route()->getController();

        /** @var BaseModel $model */
        $model = method_exists($controller, 'getBaseModel') ? $controller->getBaseModel() : null;

        $checkOwnership = $model->exists ?? false;
        if ($checkOwnership) {
            return $this->checkOwnership($model);
        }

        return $this->getActions()->where('name', $this->actionName)->isNotEmpty();
    }

    public function checkOwnership(BaseModel $model): bool
    {
        $roleActions = $this->getActions()->where('name', $this->actionName);
        if ($roleActions->isEmpty()) {
            return false;
        }

        $needCheckOnOwner = $roleActions->where('is_owner', true)->isNotEmpty();
        if ($needCheckOnOwner) {
            $ownerKey = $model->getOwnerKey();
            return auth()->id() === $model->$ownerKey;
        }

        return true;
    }

    public function getActions(): Collection
    {
        return $this->actions;
    }

    private function getUserActions(): Collection
    {
        $userId = Auth::id();
        $cacheTTL = config('rushapp_core.default_cache_ttl');
        return Cache::remember("user-actions.$userId", $cacheTTL, function () use ($userId) {
            return Action::query()
                ->join('role_action as ra', 'ra.action_id', '=', 'actions.id')
                ->join('roles as r', 'r.id', '=', 'ra.role_id')
                ->join('user_role as ur', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $userId)
                ->get();
        });
    }
}