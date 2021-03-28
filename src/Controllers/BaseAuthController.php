<?php

namespace RushApp\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Monolog\Logger;
use RushApp\Core\Exceptions\CoreHttpException;
use RushApp\Core\Models\CoreBaseModelTrait;
use RushApp\Core\Services\LoggingService;

abstract class BaseAuthController extends BaseController
{
    use CoreBaseModelTrait;

    protected string $guard;

    public function login(Request $request)
    {
        return $this->loginAttempt($request->only(['email', 'password']));
    }

    public function registerAttempt(Request $request)
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

    protected function loginAttempt(array $credentials)
    {
        if (!$token = Auth::guard($this->guard)->attempt($credentials)) {
            return $this->responseWithError(__('response_messages.incorrect_login'), 403);
        }

        return $this->successResponse(['token' => $token]);
    }

    public function refreshToken()
    {
        try {
            $token = Auth::guard($this->guard)->refresh();
        } catch (\Exception $e) {
            return $this->responseWithError(__('response_messages.token_has_been_blacklisted'), 401);
        }

        return $this->successResponse(['token' => $token]);
    }

    public function logout()
    {
        Auth::guard($this->guard)->logout();

        return $this->successResponse(['message' => __('response_messages.logout')]);
    }

    protected function getUserClass(): string
    {
        return config('boilerplate.user_model');
    }
}
