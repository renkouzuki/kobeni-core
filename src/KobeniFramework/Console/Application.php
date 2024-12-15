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
        $this->add(new Commands\MakeCommandCommand());
        $this->add(new Commands\StartCommand());
        $this->add(new Commands\MakeMigrationCommand());
    }

    public function add(Command $command)
    {
        // Store with the full signature as the key
        $this->commands[$command->getSignature()] = $command;
    }

    public function run(array $argv)
    {
        $commandName = $argv[1] ?? $this->defaultCommand;

        // For debugging
        echo "Trying to run command: $commandName\n";

        if ($commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return;
        }

        // Find the matching command
        $command = null;
        foreach ($this->commands as $signature => $cmd) {
            // Get base command name without arguments
            $baseName = explode(' ', $signature)[0];
            if ($baseName === $commandName) {
                $command = $cmd;
                break;
            }
        }

        if (!$command) {
            $this->showError($commandName);
            return;
        }

        try {
            // If there are arguments, pass them
            if (isset($argv[2])) {
                $command->setArgument('name', $argv[2]);
            }
            $command->handle();
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

        foreach ($this->commands as $signature => $command) {
            $baseName = explode(' ', $signature)[0];
            echo sprintf("  \033[36m%-15s\033[0m %s\n", $baseName, $command->getDescription());
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
