<?php

namespace KobeniFramework\Console\Commands;

use KobeniFramework\Console\Command;
use KobeniFramework\Database\DB;

class MigrateRollbackCommand extends Command
{
    protected $signature = 'migrate:rollback';
    protected $description = 'Rollback the last database migration';

    public function handle()
    {
        $db = new DB();

        try {
            // check if migrations table exists
            $db->query("SELECT 1 FROM migrations LIMIT 1");
        } catch (\Exception $e) {
            $this->error("Migrations table not found. Nothing to rollback.");
            return;
        }

        // get last batch of migrations
        $migrations = $db->query(
            "SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT 1"
        );

        if (empty($migrations)) {
            $this->info("Nothing to rollback.");
            return;
        }

        foreach ($migrations as $migration) {
            $migrationName = $migration['migration'];
            $file = getcwd() . '/database/migrations/' . $migrationName . '.php';

            if (file_exists($file)) {
                $this->info("Found migration file: " . $file);
                
                require_once $file;
                
                // extract just the timestamp part for class name
                if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $migrationName, $matches)) {
                    $className = 'Migration_' . $matches[1];
                    
                    $this->info("Looking for class: " . $className);

                    if (!class_exists($className)) {
                        $this->error("Migration class not found: " . $className);
                        continue;
                    }

                    $this->info("Rolling back: " . $migrationName);

                    try {
                        $instance = new $className();
                        $instance->down();
                        
                        $db->query(
                            "DELETE FROM migrations WHERE migration = ?",
                            [$migrationName]
                        );
                        
                        $this->info("Rollback completed: " . $migrationName);
                    } catch (\Exception $e) {
                        $this->error("Rollback failed: " . $e->getMessage());
                        $this->error("SQL Error: " . $e->getMessage());
                        break;
                    }
                } else {
                    $this->error("Invalid migration filename format: " . $migrationName);
                }
            } else {
                $this->error("Migration file not found: " . $file);
            }
        }
    }
}