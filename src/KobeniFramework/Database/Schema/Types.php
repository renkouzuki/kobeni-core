<?php

namespace KobeniFramework\Database\Schema;

class Types
{
    public static function map(string $type): string
    {
        return match ($type) {
            'char(36)' => 'char(36)',
            'string' => 'varchar(255)',
            'text' => 'text',
            'integer' => 'int',
            'bigInteger' => 'bigint',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal(10,2)',
            'boolean' => 'tinyint(1)',
            'datetime' => 'timestamp',
            'date' => 'date',
            'time' => 'time',
            'json' => 'json',
            default => $type
        };
    }
}