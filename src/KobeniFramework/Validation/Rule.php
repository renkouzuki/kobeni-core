<?php

namespace KobeniFramework\Validation;

class Rule
{
    protected array $rules = [];

    public static function create()
    {
        return new static();
    }

    public function required()
    {
        $this->rules[] = [
            'type' => 'required',
            'validate' => function ($value) {
                return $value !== null && $value !== '';
            },
            'message' => 'This field is required'
        ];
        return $this;
    }

    public function string()
    {
        $this->rules[] = [
            'type' => 'string',
            'validate' => function ($value) {
                return is_string($value);
            },
            'message' => 'Must be a string'
        ];
        return $this;
    }

    public function email()
    {
        $this->rules[] = [
            'type' => 'email',
            'validate' => function ($value) {
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            },
            'message' => 'Must be a valid email'
        ];
        return $this;
    }

    public function min($length)
    {
        $this->rules[] = [
            'type' => 'min',
            'validate' => function ($value) use ($length) {
                return strlen($value) >= $length;
            },
            'message' => "Must be at least {$length} characters"
        ];
        return $this;
    }

    public function max($length)
    {
        $this->rules[] = [
            'type' => 'max',
            'validate' => function ($value) use ($length) {
                return strlen($value) <= $length;
            },
            'message' => "Must not exceed {$length} characters"
        ];
        return $this;
    }

    public function unique($table, $column = null)
    {
        if ($column === null) {
            $column = 'name';
        }

        $this->rules[] = [
            'type' => 'unique',
            'validate' => function ($value) use ($table, $column) {
                $db = new \KobeniFramework\Database\DB();
                $result = $db->query(
                    "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
                    [$value]
                );
                return $result[0]['count'] === 0;
            },
            'message' => 'Already exists'
        ];
        return $this;
    }

    public function check($value, $allData)
    {
        $errors = [];

        foreach ($this->rules as $rule) {
            $validate = $rule['validate'];
            if (!$validate($value, $allData)) {
                $errors[] = $rule['message'];
            }
        }

        return empty($errors) ? null : $errors;
    }
}
