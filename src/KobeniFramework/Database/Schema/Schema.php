<?php

namespace KobeniFramework\Database\Schema;

class Schema
{
    protected array $models = [];
    protected array $relationships = [];
    
    public function model(string $name, callable $callback): void
    {
        $modelBuilder = new ModelBuilder($name);
        $callback($modelBuilder);
        
        $this->models[$name] = $modelBuilder->getDefinition();
        $this->relationships = array_merge(
            $this->relationships,
            $modelBuilder->getRelationships()
        );
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }
}