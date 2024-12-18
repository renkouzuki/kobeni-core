<?php

namespace KobeniFramework\Validation\Exceptions;

class ValidationException extends \Exception
{
    protected array $errors = [];

    public function __construct(array $errors, string $message = "Validation failed")
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}