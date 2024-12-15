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
        $attrs = [];
        // For updateAt fields, use CURRENT_TIMESTAMP ON UPDATE
        if ($name === 'updatedAt' || $name === 'updated_at') {
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
            if ($value === 'now()') {
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
