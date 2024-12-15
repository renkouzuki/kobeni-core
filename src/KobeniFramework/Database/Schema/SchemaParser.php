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
            $columns = [];
            $constraints = [];

            // Regular fields
            foreach ($model['fields'] as $fieldName => $field) {
                // Basic field definition
                $type = $field['type'];
                $nullable = $field['nullable'] ? 'NULL' : 'NOT NULL';
                $attributes = $this->generateAttributes($field['attributes'] ?? [], $fieldName);

                $columnDef = "$type $nullable$attributes";
                $columns[] = sprintf('"%s" => "%s"', $fieldName, $columnDef);

                // Handle unique constraint
                if (isset($field['attributes']) && in_array('@unique', $field['attributes'])) {
                    $constraints[] = sprintf('UNIQUE KEY `%s_unique` (`%s`)', $fieldName, $fieldName);
                }
            }

            // Add foreign key constraints
            foreach ($schema->getRelationships() as $relation) {
                if (strtolower($relation['model']) === strtolower($modelName)) {
                    $constraints[] = $this->generateConstraint($relation);
                }
            }

            // Combine columns and add constraints at the end
            $tableDef = [];
            foreach ($columns as $column) {
                $tableDef[] = "            " . $column;
            }
            if (!empty($constraints)) {
                foreach ($constraints as $constraint) {
                    $tableDef[] = sprintf('            "%s"', $constraint);
                }
            }

            $tables[] = sprintf(
                '$this->createTable("%s", [%s%s%s]);',
                $model['name'],
                "\n",
                implode(",\n", $tableDef),
                "\n        "
            );
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
        $type = $field['type'];
        $nullable = $field['nullable'] ? 'NULL' : 'NOT NULL';
        $attributes = $this->generateAttributes($field['attributes'] ?? [], $name);

        return sprintf(
            '"%s" => "%s %s%s"',
            $name,
            $type,
            $nullable,
            $attributes
        );
    }

    protected function generateIndexDefinition(string $fieldName, string $type = 'index'): string
    {
        return sprintf(
            '"INDEX_%s" => "KEY `%s_%s` (`%s`)"',
            $fieldName,
            $fieldName,
            $type,
            $fieldName
        );
    }

    protected function generateUniqueIndexDefinition(string $fieldName): string
    {
        return sprintf(
            '"UNIQUE_%s" => "UNIQUE KEY `%s_unique` (`%s`)"',
            $fieldName,
            $fieldName,
            $fieldName
        );
    }


    protected function generateConstraint(array $relation): string
    {
        return sprintf(
            'CONSTRAINT `fk_%s_%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`) ON DELETE CASCADE ON UPDATE CASCADE',
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

    protected function generateAttributes(array $attributes, string $fieldName): string
    {
        $result = [];

        foreach ($attributes as $attr) {
            if ($attr === '@unique') {
                // Skip unique here, it's handled as a separate constraint
                continue;
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
