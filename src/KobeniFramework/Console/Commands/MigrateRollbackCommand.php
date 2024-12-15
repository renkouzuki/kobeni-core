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

        // Get last batch of migrations
        $migrations = $db->query(
            "SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT 1"
        );

        if (empty($migrations)) {
            $this->info("Nothing to rollback.");
            return;
        }

        foreach ($migrations as $migration) {
            $file = getcwd() . '/database/migrations/' . $migration['migration'] . '.php';

            if (file_exists($file)) {
                require_once $file;

                $className = 'Migration_' . substr($migration['migration'], 18);
                $instance = new $className();

                $this->info("Rolling back: " . $migration['migration']);

                try {
                    $instance->down();
                    $db->query(
                        "DELETE FROM migrations WHERE migration = ?",
                        [$migration['migration']]
                    );
                    $this->info("Rollback completed: " . $migration['migration']);
                } catch (\Exception $e) {
                    $this->error("Rollback failed: " . $e->getMessage());
                }
            }
        }
    }
}
