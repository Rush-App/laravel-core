<?php

namespace RushApp\Core\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Monolog\Logger;
use RushApp\Core\Exceptions\CoreHttpException;
use RushApp\Core\Models\CoreBaseModelTrait;
use RushApp\Core\Services\LoggingService;

abstract class BaseAuthController extends Controller
{
    use CoreBaseModelTrait, ResponseTrait, ValidateTrait;

    protected string $guard;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        return $this->loginAttempt($request->only(['email', 'password']));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAttempt(Request $request): JsonResponse
    {
        try {
            $userClass = $this->getUserClass();
            $user = $userClass::create($request->all());
            $token = Auth::guard($this->guard)->login($user);

            return $this->successResponse(['token' => $token]);
        } catch (\Exception $e) {
            LoggingService::auth(
                Config::get('system_messages.could_not_register.message') . $e->getMessage(),
                Logger::CRITICAL
            );

            throw new CoreHttpException(409, __('response_messages.registration_error'));
        }
    }

    /** @return JsonResponse */
    public function refreshToken(): JsonResponse
    {
        try {
            $token = Auth::guard($this->guard)->refresh();
        } catch (\Exception $e) {
            return $this->responseWithError(__('response_messages.token_has_been_blacklisted'), 401);
        }

        return $this->successResponse(['token' => $token]);
    }

    /** @return JsonResponse */
    public function logout(): JsonResponse
    {
        Auth::guard($this->guard)->logout();

        return $this->successResponse(['message' => __('response_messages.logout')]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function loginAttempt(array $credentials): JsonResponse
    {
        if (!$token = Auth::guard($this->guard)->attempt($credentials)) {
            return $this->responseWithError(__('response_messages.incorrect_login'), 403);
        }

        return $this->successResponse(['token' => $token]);
    }

    /** @return string */
    private function getUserClass(): string
    {
        return config('rushapp_core.user_model');
    }
}
