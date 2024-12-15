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

        // Generate table creation code for each model
        foreach ($models as $modelName => $model) {
            $fields = [];
            foreach ($model['fields'] as $fieldName => $field) {
                $fields[] = $this->generateFieldDefinition($fieldName, $field);
            }
            
            // Add foreign key constraints
            foreach ($schema->getRelationships() as $relation) {
                if (strtolower($relation['model']) === strtolower($modelName)) {
                    $fields[] = $this->generateConstraint($relation);
                }
            }

            $tableFields = implode(",\n            ", $fields);
            $tables[] = <<<CODE
            \$this->createTable("{$model['name']}", [
            {$tableFields}
        ]);
CODE;
        }

        // Generate the complete migration class
        $template = <<<PHP
<?php

use KobeniFramework\Database\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        {$this->indentCode(implode("\n\n        ", $tables))}
    }
    
    public function down(): void
    {
        {$this->generateDownMethod($schema)}
    }
}
PHP;
        return $template;
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
        return match($type) {
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