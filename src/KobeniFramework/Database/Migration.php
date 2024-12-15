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
        array $fields,
        array $references
    ): void {
        $fields = implode('`, `', $fields);
        $references = implode('`, `', $references);
        $constraintName = "fk_{$table}_{$foreignKey}";

        $sql = "ALTER TABLE `$table` 
                ADD CONSTRAINT `$constraintName`
                FOREIGN KEY (`$fields`) 
                REFERENCES `$foreignKey`(`$references`)
                ON DELETE CASCADE
                ON UPDATE CASCADE;";

        $this->db->query($sql);
    }

    protected function dropForeignKey(string $table, string $constraintName): void
    {
        $this->db->query("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`;");
    }
}
