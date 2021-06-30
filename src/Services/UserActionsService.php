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

    /**
     * the main method to check user`s access to current endpoint
     * @return bool
     */
    public function canUserPerformAction(): bool
    {
        // if empty getCurrentAction() -> return false -> return Forbidden
        if ($this->getCurrentAction()->isNotEmpty()) {
            if (!$this->checkIsOwner()) {
                return true;
            }

            /** @var BaseModel $model */
            $model = $this->request->route()->getController()->getBaseModel();
            $ownerKey = $model->getOwnerKey();

            // checking to ownership (auth()->id() === $model->$ownerKey) where $ownerKey === user_id as default
            // if isset $model (set in BaseCrudController like $this->modelClassController::find($entityId))
            // where $entityId is first parameter from request()->route()->parameters() (only to show, update and destroy)
            // else get empty model and $model->exists === false -> return true -> get nessesary data
            return $model->exists ? auth()->id() === $model->$ownerKey : true;
        }
        return false;
    }

    /**
     * checking is_owner in role_action table for selected request()->route()->parameters() in actions table
     * @return bool
     */
    public function checkIsOwner(): bool
    {
        return $this->getCurrentAction()->where('is_owner', true)->isNotEmpty();
    }

    /**
     * get current name from actions table which matches with request()->route()->getName()
     * @return Collection
     */
    private function getCurrentAction(): Collection
    {
        return $this->actions->where('name', $this->actionName);
    }

    /**
     * get is_owner from role_action table and name from actions table
     * @return Collection
     */
    private function getUserActions(): Collection
    {
        $userId = Auth::id();
        $cacheTTL = config('rushapp_core.default_cache_ttl');
        return Cache::remember("user-actions.$userId", $cacheTTL, function () use ($userId) {
            return Action::query()
                ->select('ra.is_owner', 'actions.name as name')
                ->join('role_action as ra', 'ra.action_id', '=', 'actions.id')
                ->join('roles as r', 'r.id', '=', 'ra.role_id')
                ->join('user_role as ur', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $userId)
                ->get();
        });
    }
}

