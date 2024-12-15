<?php

namespace KobeniFramework\Database\Schema\Attributes;

class Field
{
    public function __construct(
        public string $name,
        public string $type,
        public array $attributes = [],
        public bool $nullable = false
    ) {}

    public function toString(): string
    {
        $parts = [$this->type];
        
        if (!$this->nullable) {
            $parts[] = 'NOT NULL';
        }
        
        foreach ($this->attributes as $attr) {
            if ($attr === '@unique') {
                $parts[] = 'UNIQUE';
            } elseif ($attr === '@default(uuid())') {
                $parts[] = 'DEFAULT uuid_generate_v4()';
            } elseif ($attr === '@default(now())') {
                $parts[] = 'DEFAULT CURRENT_TIMESTAMP';
            }
        }
        
        return implode(' ', $parts);
    }
}