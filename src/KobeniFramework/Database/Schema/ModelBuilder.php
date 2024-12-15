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

    public function text(string $name, bool $nullable = false): self
    {
        $this->field($name, 'text', [], $nullable);
        $this->lastField = $name;
        return $this;
    }

    public function integer(string $name, bool $nullable = false): self
    {
        $this->field($name, 'integer', [], $nullable);
        $this->lastField = $name;
        return $this;
    }

    public function decimal(string $name, bool $nullable = false): self
    {
        $this->field($name, 'decimal', [], $nullable);
        $this->lastField = $name;
        return $this;
    }

    public function foreignId(string $name): self
    {
        $this->field($name, 'char(36)', [], false);
        $this->lastField = $name;
        return $this;
    }
    
    public function datetime(string $name, bool $nullable = false): self
    {
        $attrs = [];
        if ($name === 'updated_at') {
            $attrs[] = '@default(CURRENT_TIMESTAMP)';
            $attrs[] = '@on_update(CURRENT_TIMESTAMP)';
        }
        $this->field($name, 'datetime', $attrs, $nullable);
        $this->lastField = $name;
        return $this;
    }
    
    public function unique(): self
    {
        if ($this->lastField) {
            $this->definition['fields'][$this->lastField]['attributes'][] = '@unique';
        }
        return $this;
    }

    public function default(string $value): self
    {
        if ($this->lastField) {
            if ($value === 'now()' || $value === 'CURRENT_TIMESTAMP') {
                $value = 'CURRENT_TIMESTAMP';
            }
            $this->definition['fields'][$this->lastField]['attributes'][] = "@default($value)";
        }
        return $this;
    }
    
    public function relation(string $name, string $relatedModel, array $options = []): self
    {
        $this->relationships[] = [
            'type' => 'relation',
            'name' => $name,
            'model' => $this->definition['name'],
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

    public function index(): self
    {
        if ($this->lastField) {
            $this->definition['fields'][$this->lastField]['attributes'][] = '@index';
        }
        return $this;
    }
}