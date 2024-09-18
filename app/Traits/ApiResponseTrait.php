<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
  protected function successResponse($data, $message = null, $code = 200): JsonResponse
  {
    return response()->json([
      'success' => true,
      'message' => $message,
      'data' => $data
    ], $code);
  }

  protected function errorResponse($message, $code): JsonResponse
  {
    return response()->json([
      'success' => false,
      'message' => $message,
    ], $code);
  }
}
