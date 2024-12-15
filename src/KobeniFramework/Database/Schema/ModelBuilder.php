<?php

namespace KobeniFramework\Database\Schema;

class ModelBuilder
{
    protected array $definition = [
        'fields' => [],
        'attributes' => []
    ];
    
    protected array $relationships = [];
    
    public function __construct(protected string $name)
    {
        $this->definition['name'] = $name;
    }
    
    public function id(string $name = 'id'): self
    {
        $this->field($name, 'uuid', ['@id', '@default(uuid())']);
        return $this;
    }
    
    public function string(string $name, bool $nullable = false): self
    {
        $this->field($name, 'string', [], $nullable);
        return $this;
    }
    
    public function datetime(string $name, bool $nullable = false): self
    {
        $this->field($name, 'datetime', [], $nullable);
        return $this;
    }
    
    public function unique(string $field): self
    {
        $this->definition['fields'][$field]['attributes'][] = '@unique';
        return $this;
    }
    
    public function relation(string $name, string $relatedModel, array $options = []): self
    {
        $this->relationships[] = [
            'type' => 'relation',
            'name' => $name,
            'model' => $relatedModel,
            'foreign_key' => $options['fields'] ?? null,
            'references' => $options['references'] ?? null
        ];
        return $this;
    }
    
    protected function field(string $name, string $type, array $attributes = [], bool $nullable = false): void
    {
        $this->definition['fields'][$name] = [
            'type' => $type,
            'nullable' => $nullable,
            'attributes' => $attributes
        ];
    }
    
    public function getDefinition(): array
    {
        return $this->definition;
    }
    
    public function getRelationships(): array
    {
        return $this->relationships;
    }
}