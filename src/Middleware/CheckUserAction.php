<?php

namespace RushApp\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use RushApp\Core\Services\UserActionsService;

class CheckUserAction
{
    /**
     * @var UserActionsService
     */
    private UserActionsService $userActionsService;

    public function __construct(UserActionsService $userActionsService)
    {
        $this->userActionsService = $userActionsService;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!$this->userActionsService->canUserPerformAction($request)) {
            abort(
                config('boilerplate.http_statuses.forbidden'),
                __('Forbidden')
            );
        }

        return $next($request);
    }


}