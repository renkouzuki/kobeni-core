<?php

namespace KobeniFramework\Database\Schema\Attributes;

class Relation
{
    public function __construct(
        public string $name,
        public string $type,
        public string $relatedModel,
        public array $foreignKey,
        public array $references,
        public bool $nullable = false
    ) {}

    public function toString(): string
    {
        return match ($this->type) {
            'belongsTo' => $this->generateBelongsTo(),
            'hasMany' => $this->generateHasMany(),
            'hasOne' => $this->generateHasOne(),
            'manyToMany' => $this->generateManyToMany(),
            default => ''
        };
    }

    protected function generateBelongsTo(): string
    {
        $fk = implode('", "', $this->foreignKey);
        $refs = implode('", "', $this->references);

        return sprintf(
            'FOREIGN KEY ("%s") REFERENCES "%s" ("%s")',
            $fk,
            $this->relatedModel,
            $refs
        );
    }

    protected function generateHasOne(): string
    {
        // Similar to belongsTo but reverse the foreign key and references
        $fk = implode('", "', $this->references);
        $refs = implode('", "', $this->foreignKey);

        return sprintf(
            'FOREIGN KEY ("%s") REFERENCES "%s" ("%s")',
            $fk,
            $this->name,
            $refs
        );
    }

    protected function generateHasMany(): string
    {
        // Similar to hasOne for SQL purposes
        return $this->generateHasOne();
    }

    protected function generateManyToMany(): string
    {
        // For many-to-many, we'll need to create a pivot table
        // This will be handled separately in the migration generator
        return '';
    }
}
