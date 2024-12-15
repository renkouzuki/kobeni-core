<?php

namespace KobeniFramework\Console;

class Application
{
    protected $commands = [];
    protected $defaultCommand = 'list';

    public function __construct()
    {
        $this->registerDefaultCommands();
        echo "Registered commands:\n";
        foreach ($this->commands as $name => $command) {
            echo "- $name\n";
        }
    }

    protected function registerDefaultCommands()
    {
        // Add debug
        echo "Registering commands...\n";

        try {
            $makeCommand = new Commands\MakeCommandCommand();
            echo "MakeCommand signature: " . $makeCommand->getSignature() . "\n";
            $this->add($makeCommand);
        } catch (\Exception $e) {
            echo "Error adding make:command: " . $e->getMessage() . "\n";
        }

        try {
            $startCommand = new Commands\StartCommand();
            echo "StartCommand signature: " . $startCommand->getSignature() . "\n";
            $this->add($startCommand);
        } catch (\Exception $e) {
            echo "Error adding start: " . $e->getMessage() . "\n";
        }

        // Debug registered commands
        echo "Commands in array:\n";
        var_dump(array_keys($this->commands));
    }

    public function add(Command $command)
    {
        $signature = $command->getSignature();
        $commandName = explode(' ', $signature)[0];
        $this->commands[$commandName] = $command;
    }

    public function run(array $argv)
    {
        $commandName = $argv[1] ?? $this->defaultCommand;

        echo "Looking for command: $commandName\n";
        echo "Available commands: " . implode(', ', array_keys($this->commands)) . "\n";

        if ($commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return;
        }

        if (!isset($this->commands[$commandName])) {
            $this->showError($commandName);
            return;
        }

        try {
            $this->commands[$commandName]->handle();
        } catch (\Exception $e) {
            echo "\033[31mError: {$e->getMessage()}\033[0m\n";
        }
    }

    protected function showHelp()
    {
        echo "\033[32mKobeni Framework\033[0m\n\n";
        echo "Usage:\n";
        echo "  php kobeni [command] [options]\n\n";
        echo "Available commands:\n";

        foreach ($this->commands as $name => $command) {
            echo sprintf("  \033[36m%-15s\033[0m %s\n", $name, $command->getDescription());
        }
        echo "\n";
    }

    protected function showError($command)
    {
        echo "\033[31mError: Command '$command' not found.\033[0m\n\n";
        echo "Run \033[36mphp kobeni --help\033[0m for a list of available commands.\n";
        exit(1);
    }
}
