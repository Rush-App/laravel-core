<?php

namespace RushApp\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use RushApp\Core\Services\UserActionsService;
use RushApp\Core\Exceptions\CoreHttpException;

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
            throw new CoreHttpException(403, __('core::error_messages.permission_denied'));
        }

        return $next($request);
    }
}
