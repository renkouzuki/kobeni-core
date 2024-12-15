<?php

namespace KobeniFramework\Console\Commands;

use KobeniFramework\Console\Command;
use KobeniFramework\Database\Schema\SchemaParser;

class MakeMigrationCommand extends Command
{
    protected $signature = 'make:migration {name}';
    protected $description = 'Create a new migration from schema';

    public function handle()
    {
        $name = $this->argument('name');
        $schemaPath = $this->getSchemaPath();
        
        if (!file_exists($schemaPath)) {
            $this->error("Schema file not found at: $schemaPath");
            $this->info("Creating a new schema file...");
            $this->createSchemaFile($schemaPath);
        }

        try {
            $schema = require $schemaPath;
            $parser = new SchemaParser();
            $migration = $parser->generateMigration($schema);
            
            $filename = date('Y_m_d_His') . "_{$name}.php";
            $path = $this->getMigrationPath($filename);
            
            $this->ensureMigrationDirectoryExists();
            file_put_contents($path, $migration);
            
            $this->info("Migration created successfully: $filename");
            
        } catch (\Exception $e) {
            $this->error("Failed to create migration: " . $e->getMessage());
        }
    }

    protected function getSchemaPath()
    {
        return getcwd() . '/database/schema.php';
    }

    protected function getMigrationPath($filename)
    {
        return getcwd() . '/database/migrations/' . $filename;
    }

    protected function ensureMigrationDirectoryExists()
    {
        $directory = dirname($this->getMigrationPath(''));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function createSchemaFile($path)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = $this->getSchemaTemplate();
        file_put_contents($path, $content);
        $this->info("Created new schema file at: $path");
    }

    protected function getSchemaTemplate()
    {
        return <<<'PHP'
<?php

use KobeniFramework\Database\Schema\Schema;

$schema = new Schema();

$schema->model('User', function($model) {
    $model->id()
          ->string('name')->unique()
          ->string('email')->unique()
          ->string('password')
          ->datetime('created_at')->default('now()')
          ->datetime('updated_at');
});

return $schema;
PHP;
    }
}