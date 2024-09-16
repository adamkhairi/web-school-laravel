<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $statusCode;

    public function __construct($message, $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function render()
    {
        return response()->json([
            'error' => $this->getMessage(),
        ], $this->statusCode);
    }
}
