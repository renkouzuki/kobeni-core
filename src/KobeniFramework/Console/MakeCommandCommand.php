<?php

namespace KobeniFramework\Console\Commands;

use KobeniFramework\Console\Command;

class MakeCommandCommand extends Command
{
    protected $signature = 'make:command {name}';
    protected $description = 'Create a new console command';

    public function handle()
    {
        $name = $this->argument('name');
        $path = $this->getCommandPath($name);
        
        if (file_exists($path)) {
            $this->error("Command already exists!");
            return;
        }

        $this->createCommand($name, $path);
        $this->info("Command created successfully: {$name}");
    }

    protected function getCommandPath($name)
    {
        return getcwd() . "/src/App/Console/Commands/{$name}.php";
    }

    protected function createCommand($name, $path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getCommandTemplate($name);
        file_put_contents($path, $content);
    }

    protected function getCommandTemplate($name)
    {
        return <<<PHP
<?php

namespace App\Console\Commands;

use KobeniFramework\Console\Command;

class {$name} extends Command
{
    protected \$signature = 'command:name';
    protected \$description = 'Command description';

    public function handle()
    {
        \$this->info('Command is working!');
    }
}
PHP;
    }
}