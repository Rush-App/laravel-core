<?php

namespace RushApp\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ValidateTrait
{
    /**
     * @param Request $request
     * @param string|null $requestClass
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateRequest(Request $request, ?string $requestClass): void
    {
        if ($requestClass) {
            $validator = Validator::make(
                $request->all(),
                resolve($requestClass)->rules()
            );
            $validator->validate();
        }
    }
}
