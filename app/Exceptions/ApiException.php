<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(string $message = '', int $statusCode = 400, \Throwable $previous = null, array $headers = [])
    {
        parent::__construct($statusCode, $message, $previous, $headers);
    }
}
