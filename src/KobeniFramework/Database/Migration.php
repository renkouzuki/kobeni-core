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
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete = 'CASCADE',
        string $onUpdate = 'CASCADE'
    ): void {
        $constraintName = "fk_{$table}_{$column}";
        $sql = "ALTER TABLE `$table` 
                ADD CONSTRAINT `$constraintName`
                FOREIGN KEY (`$column`) 
                REFERENCES `$referenceTable`(`$referenceColumn`)
                ON DELETE $onDelete
                ON UPDATE $onUpdate;";
        
        $this->db->query($sql);
    }
    
    protected function dropForeignKey(string $table, string $foreignKey): void
    {
        $this->db->query("ALTER TABLE `$table` DROP FOREIGN KEY `$foreignKey`;");
    }
}