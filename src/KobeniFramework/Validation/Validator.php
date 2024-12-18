<?php

namespace KobeniFramework\Validation;

class Validator
{
    protected $data;
    protected $errors = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public static function make($data)
    {
        return new static($data);
    }

    public function validate($rules)
    {
        $this->errors = [];

        foreach ($rules as $field => $rule) {
            if ($rule instanceof Rule) {
                $value = isset($this->data[$field]) ? $this->data[$field] : null;
                if ($errors = $rule->check($value, $this->data)) {
                    $this->errors[$field] = $errors;
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}