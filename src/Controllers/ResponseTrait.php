<?php

namespace RushApp\Core\Controllers;

use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    /**
     * @param $responseData
     * @return JsonResponse
     */
    protected function successResponse($responseData): JsonResponse
    {
        return response()->json($responseData);
    }

    /**
     * @param string $error
     * @param int $code
     * @return JsonResponse
     */
    protected function responseWithError(string $error, int $code): JsonResponse
    {
        return response()->json(['error' => $error], $code);
    }
}
