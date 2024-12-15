<?php

namespace KobeniFramework\Database;

abstract class Migration
{
    protected DB $db;

    public function __construct()
    {
        $this->db = new DB();
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function createTable(string $table, array $fields): void
    {
        $regularFields = [];
        $constraints = [];

        foreach ($fields as $name => $def) {
            if (
                str_starts_with($def, 'FOREIGN KEY') ||
                str_starts_with($def, 'PRIMARY KEY') ||
                str_starts_with($def, 'CONSTRAINT')
            ) {
                $constraints[] = "    $def";
            } else {
                $regularFields[] = "    `$name` $def";
            }
        }

        // Combine all fields and constraints
        $allDefinitions = array_merge($regularFields, $constraints);
        $fieldsStr = implode(",\n", $allDefinitions);

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (\n$fieldsStr\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $this->db->query($sql);
        } catch (\Exception $e) {
            echo "Failed to create table with SQL:\n$sql\n";
            throw $e;
        }
    }

    protected function dropTable(string $table): void
    {
        $this->db->query("DROP TABLE IF EXISTS `$table` CASCADE;");
    }

    protected function addForeignKey(
        string $table,
        string $foreignKey,
        string $referenceTable,
        string $referenceColumn
    ): void {
        $constraintName = "fk_{$table}_{$foreignKey}";
        $sql = "ALTER TABLE `$table` 
                ADD CONSTRAINT `$constraintName`
                FOREIGN KEY (`$foreignKey`) 
                REFERENCES `$referenceTable`(`$referenceColumn`)
                ON DELETE CASCADE
                ON UPDATE CASCADE;";

        try {
            $this->db->query($sql);
        } catch (\Exception $e) {
            echo "Failed to add foreign key with SQL:\n$sql\n";
            throw $e;
        }
    }
}
