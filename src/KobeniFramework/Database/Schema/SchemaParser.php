<?php

namespace KobeniFramework\Database\Schema;

use KobeniFramework\Database\DB;

class SchemaParser
{
    public function getDatabaseName(): string{
        
        DB::loadEnvironment();

        $config = DB::loadConfig();
        return $config['DB_DATABASE'];
    }

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
        $dbName = $this->getDatabaseName();

        foreach ($models as $modelName => $model) {
            $alterLogic = [];
            $alterLogic[] = '$this->db->query("SET FOREIGN_KEY_CHECKS=0;");';

            // check if table exists
            $checkTable = sprintf(
                '$tableExists = $this->db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = \'%s\' AND table_name = \'%s\'");',
                $dbName,
                $model['name']
            );
            $alterLogic[] = $checkTable;
            $alterLogic[] = 'if (empty($tableExists)) {';

            // create table logic
            $createTable = $this->generateCreateTable($model);
            $alterLogic[] = '    ' . $createTable;

            $alterLogic[] = '} else {';

            // get all current columns
            $alterLogic[] = sprintf(
                '    $currentColumns = $this->db->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = \'%s\' AND table_name = \'%s\'");',
                $dbName,
                $model['name']
            );

            // drop columns that no longer exist in schema
            $alterLogic[] = '    $existingColumns = array_column($currentColumns, "COLUMN_NAME");';
            $alterLogic[] = '    $schemaColumns = ' . var_export(array_keys($model['fields']), true) . ';';
            $alterLogic[] = '    $columnsToRemove = array_diff($existingColumns, $schemaColumns);';
            $alterLogic[] = '    foreach ($columnsToRemove as $column) {';
            $alterLogic[] = '        if ($column !== "id") {';
            $alterLogic[] = sprintf(
                '            $this->db->query("ALTER TABLE `%s` DROP COLUMN `$column`");',
                $model['name']
            );
            $alterLogic[] = '        }';
            $alterLogic[] = '    }';

            // modify or add columns
            foreach ($model['fields'] as $fieldName => $field) {
                if ($fieldName !== 'id') {
                    $columnDef = $this->generateColumnDefinition($fieldName, $field);
                    $checkColumn = sprintf(
                        '    $columnExists = $this->db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = \'%s\' AND table_name = \'%s\' AND column_name = \'%s\'");',
                        $dbName,
                        $model['name'],
                        $fieldName
                    );

                    $alterLogic[] = $checkColumn;
                    $alterLogic[] = '    if (empty($columnExists)) {';
                    $alterLogic[] = sprintf(
                        '        $this->db->query("ALTER TABLE `%s` ADD COLUMN %s");',
                        $model['name'],
                        $columnDef
                    );
                    $alterLogic[] = '    } else {';
                    $alterLogic[] = sprintf(
                        '        $this->db->query("ALTER TABLE `%s` MODIFY COLUMN %s");',
                        $model['name'],
                        $columnDef
                    );
                    $alterLogic[] = '    }';
                }
            }

            // handle relationship modifications
            if (isset($model['relationships'])) {
                foreach ($model['relationships'] as $relation) {
                    if ($relation['type'] === 'belongsTo') {
                        $foreignKey = $relation['foreignKey'];
                        $nullable = $relation['nullable'] ?? false;

                        $alterLogic[] = sprintf(
                            '    $this->db->query("ALTER TABLE `%s` MODIFY COLUMN `%s` char(36) %s");',
                            $model['name'],
                            $foreignKey,
                            $nullable ? 'NULL' : 'NOT NULL'
                        );
                    }
                }
            }

            $alterLogic[] = '}';
            $alterLogic[] = '$this->db->query("SET FOREIGN_KEY_CHECKS=1;");';

            $tables[] = implode("\n        ", $alterLogic);
        }

        $migrations = implode("\n\n        ", $tables);
        $downMethod = $this->generateDownMethod($schema);

        return <<<PHP
<?php

use KobeniFramework\Database\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        {$migrations}
    }
    
    public function down(): void
    {
        {$downMethod}
    }
}
PHP;
    }

    protected function sortModelsByDependency(Schema $schema): array
    {
        $models = $schema->getModels();
        $sorted = [];
        $relationships = $schema->getRelationships();

        foreach ($models as $name => $model) {
            if (!$this->hasRelationships($name, $relationships)) {
                $sorted[$name] = $model;
            }
        }

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
            strtolower($relation['model']),
            $relation['foreign_key'][0],
            $relation['foreign_key'][0],
            strtolower($relation['name']),
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
                continue;  // skip unique hereit handled as a separate constraint
            } elseif ($attr === '@default(UUID())') {
                $result[] = 'DEFAULT UUID()'; // ensure uuid() is used as a function idk about this yet it should be function or not
            } elseif ($attr === '@default(CURRENT_TIMESTAMP)') {
                $result[] = 'DEFAULT CURRENT_TIMESTAMP';
            } elseif ($attr === '@on_update(CURRENT_TIMESTAMP)') {
                $result[] = 'ON UPDATE CURRENT_TIMESTAMP';
            } elseif (str_starts_with($attr, '@default(')) {
                $value = substr($attr, 9, -1); // extract value for other defaults
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

    protected function generateColumnDefinition(string $name, array $field): string
    {
        $nullable = $field['nullable'] ? 'NULL' : 'NOT NULL';
        $attributes = $this->generateAttributes($field['attributes'] ?? [], $name);
        return sprintf('`%s` %s %s%s', $name, $field['type'], $nullable, $attributes);
    }

    protected function generateCreateTable($model): string
    {
        $columns = [];
        $constraints = [];

        foreach ($model['fields'] as $fieldName => $field) {
            if ($fieldName === 'id') {
                $columns[] = sprintf('`%s` %s NOT NULL DEFAULT UUID() PRIMARY KEY', $fieldName, $field['type']);
            } else {
                $nullable = $field['nullable'] ? 'NULL' : 'NOT NULL';
                $attributes = $this->generateAttributes($field['attributes'] ?? [], $fieldName);
                $columns[] = sprintf('`%s` %s %s%s', $fieldName, $field['type'], $nullable, $attributes);
            }

            if (isset($field['attributes']) && in_array('@unique', $field['attributes'])) {
                $constraints[] = sprintf('UNIQUE KEY `%s_unique` (`%s`)', $fieldName, $fieldName);
            }
        }

        $allFields = array_merge($columns, $constraints);
        $fieldsStr = implode(",\n    ", $allFields);

        return sprintf(
            '$this->db->query("CREATE TABLE IF NOT EXISTS `%s` (\n    %s\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");',
            $model['name'],
            $fieldsStr
        );
    }
}
