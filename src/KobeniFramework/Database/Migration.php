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
            fn($name, $def) => "    $name $def",
            array_keys($fields),
            $fields
        ));

        $sql = "CREATE TABLE IF NOT EXISTS $table (\n$fieldsStr\n)";
        $this->db->query($sql);
    }

    protected function dropTable(string $table): void
    {
        $this->db->query("DROP TABLE IF EXISTS $table");
    }

    protected function addForeignKey(
        string $table,
        string $column,
        string $referenceTable,
        string $referenceColumn
    ): void {
        $this->db->query(
            "ALTER TABLE $table ADD FOREIGN KEY ($column) 
             REFERENCES $referenceTable($referenceColumn)"
        );
    }
}
