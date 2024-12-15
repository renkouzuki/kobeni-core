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
        $fieldsStr = implode(",\n", array_map(
            fn($name, $def) => "    `$name` $def",
            array_keys($fields),
            $fields
        ));

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (\n$fieldsStr\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->query($sql);
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

        $this->db->query($sql);
    }
}
