<?php

namespace KobeniFramework\Database\Schema;

class SchemaParser
{
    public function generateMigration(Schema $schema): string
    {
        $timestamp = date('Y_m_d_His');
        $className = 'Migration_' . $timestamp;
        return $this->generateMigrationClass($className, $schema);
    }

    protected function generateMigrationClass(string $className, Schema $schema): string
    {
        $tables = [];
        $models = $this->sortModelsByDependency($schema);

        foreach ($models as $modelName => $model) {
            $fields = [];
            $constraints = [];
            $indexes = [];

            // Regular fields
            foreach ($model['fields'] as $fieldName => $field) {
                if (isset($field['attributes']) && in_array('@unique', $field['attributes'])) {
                    $indexes[] = "UNIQUE KEY `{$fieldName}_unique` (`{$fieldName}`)";
                }
                if (isset($field['attributes']) && in_array('@index', $field['attributes'])) {
                    $indexes[] = "KEY `{$fieldName}_index` (`{$fieldName}`)";
                }
                $fields[] = $this->generateFieldDefinition($fieldName, $field);
            }

            // Foreign keys
            foreach ($schema->getRelationships() as $relation) {
                if (strtolower($relation['model']) === strtolower($modelName)) {
                    $fkColumn = $relation['foreign_key'][0];
                    $indexes[] = "KEY `{$fkColumn}_index` (`{$fkColumn}`)";
                    $constraints[] = $this->generateConstraint($relation);
                }
            }

            // Combine all fields, indexes, and constraints
            $allDefinitions = array_merge(
                $fields,
                $indexes,
                $constraints
            );

            $tableFields = implode(",\n            ", $allDefinitions);
            $tables[] = <<<CODE
            \$this->createTable("{$model['name']}", [
            {$tableFields}
        ]);
CODE;
        }

        return <<<PHP
<?php

use KobeniFramework\Database\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        {$this->indentCode(implode("\n\n        ",$tables))}
    }
    
    public function down(): void
    {
        {$this->generateDownMethod($schema)}
    }
}
PHP;
    }

    protected function sortModelsByDependency(Schema $schema): array
    {
        $models = $schema->getModels();
        $sorted = [];
        $relationships = $schema->getRelationships();

        // First add models without relationships
        foreach ($models as $name => $model) {
            if (!$this->hasRelationships($name, $relationships)) {
                $sorted[$name] = $model;
            }
        }

        // Then add models with relationships
        foreach ($models as $name => $model) {
            if ($this->hasRelationships($name, $relationships)) {
                $sorted[$name] = $model;
            }
        }

        return $sorted;
    }

    protected function hasRelationships(string $modelName, array $relationships): bool
    {
        foreach ($relationships as $relation) {
            if (strtolower($relation['model']) === strtolower($modelName)) {
                return true;
            }
        }
        return false;
    }

    protected function generateFieldDefinition(string $name, array $field): string
    {
        $type = $this->mapFieldType($field['type']);
        $nullable = $field['nullable'] ? 'NULL' : 'NOT NULL';
        $attributes = $this->generateAttributes($field['attributes'] ?? []);

        if (isset($field['attributes']) && in_array('@id', $field['attributes'])) {
            $type .= ' PRIMARY KEY';
        }

        return sprintf(
            '"%s" => "%s %s%s"',
            $name,
            $type,
            $nullable,
            $attributes
        );
    }


    protected function generateConstraint(array $relation): string
    {
        return sprintf(
            '"CONSTRAINT fk_%s_%s FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE CASCADE ON UPDATE CASCADE"',
            $relation['model'],
            $relation['foreign_key'][0],
            $relation['foreign_key'][0],
            $relation['name'],
            $relation['references'][0]
        );
    }

    protected function generateDownMethod(Schema $schema): string
    {
        $drops = [];
        $models = array_reverse($schema->getModels());

        foreach ($models as $modelName => $model) {
            $drops[] = "\$this->dropTable(\"{$model['name']}\");";
        }

        return implode("\n        ", $drops);
    }

    protected function mapFieldType(string $type): string
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

    protected function generateAttributes(array $attributes): string
    {
        $result = [];

        foreach ($attributes as $attr) {
            if ($attr === '@unique') {
                $result[] = 'UNIQUE';
            } elseif ($attr === '@default(UUID())') {
                $result[] = 'DEFAULT (UUID())';
            } elseif ($attr === '@default(CURRENT_TIMESTAMP)') {
                $result[] = 'DEFAULT CURRENT_TIMESTAMP';
            } elseif ($attr === '@on_update(CURRENT_TIMESTAMP)') {
                $result[] = 'ON UPDATE CURRENT_TIMESTAMP';
            } elseif (str_starts_with($attr, '@default(')) {
                $value = substr($attr, 9, -1);
                $result[] = "DEFAULT $value";
            }
        }

        return $result ? ' ' . implode(' ', $result) : '';
    }

    protected function indentCode(string $code, int $spaces = 8): string
    {
        $lines = explode("\n", $code);
        $indent = str_repeat(' ', $spaces);
        return implode("\n" . $indent, $lines);
    }
}
