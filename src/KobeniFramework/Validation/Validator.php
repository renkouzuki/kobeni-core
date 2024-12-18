<?php

namespace KobeniFramework\Validation;

use ArrayAccess;
use KobeniFramework\Controllers\RequestDataMixing\MixedAccessData;

class Validator
{
    protected $data;
    protected array $errors = [];

    public function __construct($data)
    {
        if ($data instanceof ArrayAccess) {
            $this->data = $data;
        } elseif (is_array($data)) {
            $this->data = new MixedAccessData($data);
        } else {
            throw new \InvalidArgumentException('Data must be an array or implement ArrayAccess');
        }
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
