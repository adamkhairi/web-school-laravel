<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function respondWithJson($data, $statusCode = 200)
    {
        return response()->json($data, $statusCode);
    }

    protected function validateRequest(array $rules)
    {
        return request()->validate($rules);
    }
}
