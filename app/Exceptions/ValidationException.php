<?php

namespace App\Exceptions;

use Exception;

/**
 * This class formats validation errors so that they can
 *  be passed with redirect back and logs the error on the server
 * 
 * @param string $message - The error description message
 * @param array $errors - an array of all the errors with keys as the individual error name and value as error explanation. default is []
 * @param int $status - HTTP status code
 * @param array $contect - a detailed explanation on what may be causing the error and possible fixes
 */
class ValidationException extends Exception
{
    public function __construct(
        string $message = "Application error",
        public array $errors = [],
        public int $status = 400,
        public array $context = []
    ) {
        parent::__construct($message, $status);

        # Log the exception with the user who caused the exception
        $errorMessageArray = [
            'message' => $message,
            'errors' => $errors,
            'status' => $status,
            'context' => $context,
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