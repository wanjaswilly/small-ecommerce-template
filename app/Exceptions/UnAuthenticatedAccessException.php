<?php

namespace App\Exceptions;

use Exception;

/**
 * This exception is thrown if an unauthenticated user tries to access a resource.
 * they are redirected to login page with the given error
 * 
 */
class UnAuthenticatedAccessException extends Exception
{
/**
 * Summary of __construct
 * @param string $message - error message
 * @param array $errors - an array of all errors 
 * @param int $status - HTTP code
 * @return array array [<string, mixed>]
 */
public function __construct(
        string $message = "Application error",
        public array $errors = [],
        public int $status = 400,
    ) {
        parent::__construct($message, $status);

        # Log the exception with the user who caused the exception
        $errorMessageArray = [
            'message' => $message,
            'errors' => $errors,
            'status' => $status,
        ];
        error_log(json_encode($errorMessageArray));
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'status' => $this->status,
            'context' => $this->context
        ];
    }

}