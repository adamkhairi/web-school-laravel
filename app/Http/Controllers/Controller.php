<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function respondWithJson($data, $statusCode = 200)
    {
        return response()->json($data, $statusCode);
    }

    // Error Handling and Validation
    protected function sendFailedResponse($message, $status): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }

    protected function validateRequest(array $rules)
    {
        return request()->validate($rules);
    }
}
