<?php

namespace KobeniFramework\Database\Schema;

class SchemaParser
{
    public function generateMigration(Schema $schema): string
    {
        $timestamp = date('Y_m_d_His');
        $className = 'Migration_' . $timestamp;

        $template = <<<PHP
<?php

use KobeniFramework\Database\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        // Create Role table first
        \$this->createTable("role", [
            "id" => "char(36) NOT NULL DEFAULT (UUID())",
            "name" => "varchar(255) NOT NULL UNIQUE",
            "description" => "varchar(255) NULL",
            "created_at" => "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "updated_at" => "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ]);

        // Create User table with foreign key constraint
        \$this->createTable("user", [
            "id" => "char(36) NOT NULL DEFAULT (UUID())",
            "name" => "varchar(255) NOT NULL UNIQUE",
            "email" => "varchar(255) NOT NULL UNIQUE",
            "password" => "varchar(255) NOT NULL",
            "profile" => "varchar(255) NULL",
            "role_id" => "char(36) NOT NULL",
            "deleted_at" => "timestamp NULL",
            "created_at" => "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "updated_at" => "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE ON UPDATE CASCADE" // Added directly in table creation
        ]);
    }
    
    public function down(): void
    {
        \$this->dropTable("user");
        \$this->dropTable("role");
    }
}
PHP;

        return $template;
    }

//     protected function generateMigrationClass(string $className, Schema $schema): string
//     {
//         $template = <<<PHP
// <?php

// use KobeniFramework\Database\Migration;

// class {$className} extends Migration
// {
//     public function up(): void
//     {
//         // Create tables first
//         {$this->generateTableCreations($schema)}

//         // Then add foreign key constraints
//         {$this->generateRelationships($schema)}
//     }
    
//     public function down(): void
//     {
//         {$this->generateDownMethod($schema)}
//     }
// }
// PHP;
//         return $template;
//     }

    protected function generateUpMethod(Schema $schema): string
    {
        $code = [];

        // First, create independent tables (those without foreign keys)
        foreach ($schema->getModels() as $model) {
            if (!$this->hasRelationships($model['name'], $schema->getRelationships())) {
                $code[] = $this->generateCreateTableStatement($model);
            }
        }

        // Then create tables with foreign keys
        foreach ($schema->getModels() as $model) {
            if ($this->hasRelationships($model['name'], $schema->getRelationships())) {
                $code[] = $this->generateCreateTableStatement($model);
            }
        }

        // Finally add the foreign key constraints
        foreach ($schema->getRelationships() as $relation) {
            $code[] = $this->generateRelationshipStatement($relation);
        }

        return implode("\n\n        ", $code);
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
        // Debug line
        error_log("Generating table for model: " . json_encode($model));

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
        // Debug line
        error_log("Generating relationship: " . json_encode($relation));

        if ($relation['type'] === 'relation') {
            return sprintf(
                '$this->addForeignKey("%s", "%s", "%s", "%s");',
                $relation['model'],                  // table name
                $relation['foreign_key'][0],         // foreign key column
                strtolower($relation['name']),       // referenced table
                $relation['references'][0]           // referenced column
            );
        }
        return '';
    }

    protected function generateFieldDefinition(string $name, array $field): string
    {
        // Debug line
        error_log("Generating field definition for: $name - " . json_encode($field));

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

    protected function generateTableCreations(Schema $schema): string
    {
        $code = [];
        foreach ($schema->getModels() as $model) {
            $code[] = $this->generateCreateTableStatement($model);
        }
        return implode("\n\n        ", $code);
    }

    protected function generateRelationships(Schema $schema): string
    {
        $code = [];
        foreach ($schema->getRelationships() as $relation) {
            if ($relation['type'] === 'relation') {
                $code[] = sprintf(
                    '$this->addForeignKey("%s", "%s", "%s", "%s");',
                    strtolower($relation['model']),      // table
                    $relation['foreign_key'][0],         // foreign key column
                    strtolower($relation['name']),       // referenced table
                    $relation['references'][0]           // referenced column
                );
            }
        }
        return implode("\n        ", $code);
    }
}
