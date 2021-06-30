<?php

namespace RushApp\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use RushApp\Core\Services\UserActionsService;

class CheckUserAction
{
    /** @var UserActionsService */
    private UserActionsService $userActionsService;

    public function __construct(UserActionsService $userActionsService)
    {
        $this->userActionsService = $userActionsService;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->userActionsService->canUserPerformAction()) {
            abort(
                config('rushapp_core.http_statuses.forbidden'),
                __('Forbidden')
            );
        }

        return $next($request);
    }
}
