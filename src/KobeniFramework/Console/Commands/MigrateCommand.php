<?php

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
        
        // Get all migration files
        $files = glob(getcwd() . '/database/migrations/*.php');
        sort($files); // Sort by timestamp
        
        $this->info("Found " . count($files) . " migration files.");
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            // Check if migration was already run
            if (!$this->hasRun($filename)) {
                require_once $file;
                
                // Get class name from the file content
                $content = file_get_contents($file);
                if (preg_match('/class\s+(\w+)\s+extends\s+Migration/i', $content, $matches)) {
                    $className = $matches[1];
                    
                    $this->info("Running migration: $filename");
                    
                    try {
                        $migration = new $className();
                        $migration->up();
                        $this->logMigration($filename);
                        $this->info("Migration completed: $filename");
                    } catch (\Exception $e) {
                        $this->error("Migration failed: " . $e->getMessage());
                        break; // Stop on first error
                    }
                } else {
                    $this->error("Could not find migration class in file: $filename");
                }
            } else {
                $this->info("Skipping already run migration: $filename");
            }
        }
        
        $this->info("Migration process completed!");
    }

    protected function createMigrationsTable()
    {
        try {
            $db = new DB();
            $db->query(
                "CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            );
            $this->info("Migrations table checked/created.");
        } catch (\Exception $e) {
            $this->error("Failed to create migrations table: " . $e->getMessage());
            exit(1);
        }
    }

    protected function hasRun(string $migration): bool
    {
        try {
            $db = new DB();
            $result = $db->query(
                "SELECT COUNT(*) as count FROM migrations WHERE migration = ?",
                [$migration]
            );
            return $result[0]['count'] > 0;
        } catch (\Exception $e) {
            $this->error("Failed to check migration status: " . $e->getMessage());
            return false;
        }
    }

    protected function logMigration(string $migration): void
    {
        try {
            $db = new DB();
            $db->query(
                "INSERT INTO migrations (migration) VALUES (?)",
                [$migration]
            );
        } catch (\Exception $e) {
            $this->error("Failed to log migration: " . $e->getMessage());
        }
    }
}