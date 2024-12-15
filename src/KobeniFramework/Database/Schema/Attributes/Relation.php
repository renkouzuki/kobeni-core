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
        $fk = implode('", "', $this->references); /// generate reverse foreign key and references instead it similar to belongTo maybe it should be using one service instead
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
        return $this->generateHasOne(); // i fix this one just recyle the top method instead 
    }

    protected function generateManyToMany(): string
    {
        return ''; /// this one should be handle seperately in the migration generator
    }
}
