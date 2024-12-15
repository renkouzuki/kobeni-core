<?php

namespace KobeniFramework\Database\Schema;

class ModelBuilder
{
    protected array $definition = [
        'fields' => [],
        'attributes' => []
    ];
    
    protected array $relationships = [];
    protected ?string $lastField = null;
    
    public function __construct(protected string $name)
    {
        $this->definition['name'] = strtolower($name);
    }
    
    public function id(string $name = 'id'): self
    {
        $this->field($name, 'char(36)', ['@id', '@default(UUID())']);
        $this->lastField = $name;
        return $this;
    }
    
    public function string(string $name, bool $nullable = false): self
    {
        $this->field($name, 'string', [], $nullable);
        $this->lastField = $name;
        return $this;
    }
    
    public function datetime(string $name, bool $nullable = false): self
    {
        $this->field($name, 'datetime', [], $nullable);
        $this->lastField = $name;
        return $this;
    }
    
    public function unique(): self  // Changed to remove parameter requirement
    {
        if (!$this->lastField) {
            throw new \RuntimeException('No field defined before calling unique()');
        }
        
        if (!isset($this->definition['fields'][$this->lastField])) {
            throw new \RuntimeException("Field {$this->lastField} not found");
        }
        
        if (!isset($this->definition['fields'][$this->lastField]['attributes'])) {
            $this->definition['fields'][$this->lastField]['attributes'] = [];
        }
        
        $this->definition['fields'][$this->lastField]['attributes'][] = '@unique';
        return $this;
    }

    public function default(string $value): self
    {
        if (!$this->lastField) {
            throw new \RuntimeException('No field defined before calling default()');
        }
        
        $this->definition['fields'][$this->lastField]['attributes'][] = "@default($value)";
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
        $this->lastField = null; // Reset lastField after relation
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