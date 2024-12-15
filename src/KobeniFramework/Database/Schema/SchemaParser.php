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
        $template = <<<PHP
<?php

use KobeniFramework\Database\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        {$this->generateUpMethod($schema)}
    }
    
    public function down(): void
    {
        {$this->generateDownMethod($schema)}
    }
}
PHP;
        return $template;
    }

    protected function generateUpMethod(Schema $schema): string
    {
        $code = [];
        foreach ($schema->getModels() as $model) {
            $code[] = $this->generateCreateTableStatement($model);
        }

        foreach ($schema->getRelationships() as $relation) {
            $code[] = $this->generateRelationshipStatement($relation);
        }

        return implode("\n\n", $code);
    }

    protected function generateDownMethod(Schema $schema): string
    {
        $code = [];
        $models = array_reverse($schema->getModels());

        foreach ($models as $model) {
            $code[] = sprintf('$this->dropTable("%s");', $model['name']);
        }

        return implode("\n", $code);
    }

    protected function generateCreateTableStatement(array $model): string
    {
        $fields = [];
        foreach ($model['fields'] as $name => $field) {
            $fields[] = $this->generateFieldDefinition($name, $field);
        }

        return sprintf(
            '$this->createTable("%s", [%s]);',
            $model['name'],
            implode(",\n            ", $fields)
        );
    }

    protected function generateRelationshipStatement(array $relation): string
    {
        if ($relation['type'] === 'relation') {
            $foreignKey = implode('", "', (array)$relation['foreign_key']);
            $references = implode('", "', (array)$relation['references']);

            return sprintf(
                '$this->addForeignKey("%s", "%s", ["%s"], ["%s"]);',
                $relation['model'],
                $relation['name'],
                $foreignKey,
                $references
            );
        }
        return '';
    }

    protected function generateFieldDefinition(string $name, array $field): string
    {
        $attributes = $this->generateAttributes($field['attributes'] ?? []);
        $type = $this->mapFieldType($field['type']);
        $nullable = $field['nullable'] ? 'NULL' : 'NOT NULL';

        return sprintf(
            '"%s" => "%s %s%s"',
            $name,
            $type,
            $nullable,
            $attributes
        );
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

    protected function mapFieldType(string $type): string
    {
        return match ($type) {
            'uuid' => 'char(36)',
            'string' => 'varchar(255)',
            'datetime' => 'timestamp',
            default => $type
        };
    }
}
