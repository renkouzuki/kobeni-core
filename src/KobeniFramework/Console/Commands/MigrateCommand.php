<?php
// src/KobeniFramework/Console/Commands/MigrateCommand.php

namespace KobeniFramework\Console\Commands;

use KobeniFramework\Console\Command;
use KobeniFramework\Database\DB;

class MigrateCommand extends Command
{
    protected $signature = 'migrate';
    protected $description = 'Run the database migrations';

    public function handle()
    {
        $this->createMigrationsTable();

        $files = glob(getcwd() . '/database/migrations/*.php');
        sort($files);

        foreach ($files as $file) {
            $filename = basename($file, '.php');

            if (!$this->hasRun($filename)) {
                require_once $file;

                $className = 'Migration_' . substr($filename, 18);
                $migration = new $className();

                $this->info("Running migration: $filename");

                try {
                    $migration->up();
                    $this->logMigration($filename);
                    $this->info("Migration completed: $filename");
                } catch (\Exception $e) {
                    $this->error("Migration failed: " . $e->getMessage());
                }
            }
        }

        $this->info("All migrations completed!");
    }

    protected function createMigrationsTable()
    {
        $db = new DB();
        $db->query(
            "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    protected function hasRun(string $migration): bool
    {
        $db = new DB();
        $result = $db->query(
            "SELECT COUNT(*) as count FROM migrations WHERE migration = ?",
            [$migration]
        );
        return $result[0]['count'] > 0;
    }

    protected function logMigration(string $migration): void
    {
        $db = new DB();
        $db->query(
            "INSERT INTO migrations (migration) VALUES (?)",
            [$migration]
        );
    }
}
