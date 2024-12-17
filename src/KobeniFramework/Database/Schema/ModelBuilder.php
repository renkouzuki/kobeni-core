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
        $this->field($name, 'char(36)', ['@default(UUID())']);
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
            'type' => Types::map($type),
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

    public function hasOne(string $model, ?string $foreignKey = null, ?string $localKey = 'id', bool $nullable = false): self
    {
        $foreignKey = $foreignKey ?? strtolower($this->name) . '_id';
        $this->definition['relationships'][] = [
            'type' => 'hasOne',
            'model' => $model,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey,
            'nullable' => $nullable
        ];
        return $this;
    }

    public function hasMany(string $model, ?string $foreignKey = null, ?string $localKey = 'id', bool $nullable = false): self
    {
        $foreignKey = $foreignKey ?? strtolower($this->name) . '_id';
        $this->definition['relationships'][] = [
            'type' => 'hasMany',
            'model' => $model,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey,
            'nullable' => $nullable
        ];
        return $this;
    }

    public function belongsTo(string $model, ?string $foreignKey = null, ?string $ownerKey = 'id', bool $nullable = false): self
    {
        $foreignKey = $foreignKey ?? strtolower($model) . '_id';
        $this->foreignId($foreignKey)->nullable($nullable);

        $this->definition['relationships'][] = [
            'type' => 'belongsTo',
            'model' => $model,
            'foreignKey' => $foreignKey,
            'ownerKey' => $ownerKey,
            'nullable' => $nullable  // Add nullable flag
        ];
        return $this;
    }

    public function belongsToMany(
        string $model,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        bool $nullable = false
    ): self {
        $table = $table ?? $this->createPivotTableName($this->name, $model);
        $foreignPivotKey = $foreignPivotKey ?? strtolower($this->name) . '_id';
        $relatedPivotKey = $relatedPivotKey ?? strtolower($model) . '_id';

        $this->definition['relationships'][] = [
            'type' => 'belongsToMany',
            'model' => $model,
            'table' => $table,
            'foreignPivotKey' => $foreignPivotKey,
            'relatedPivotKey' => $relatedPivotKey,
            'nullable' => $nullable
        ];
        return $this;
    }

    protected function createPivotTableName(string $model1, string $model2): string
    {
        $models = [strtolower($model1), strtolower($model2)];
        sort($models);
        return implode('_', $models);
    }

    public function nullable(bool $value = true): self
    {
        if ($this->lastField) {
            $this->definition['fields'][$this->lastField]['nullable'] = $value;
        }
        return $this;
    }
}
