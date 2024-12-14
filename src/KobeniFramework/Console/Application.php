<?php

namespace KobeniFramework\Console;

class Application
{
    protected $commands = [];
    protected $defaultCommand = 'list';

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    protected function registerDefaultCommands()
    {
        $this->add(new Commands\StartCommand());
    }

    public function add(Command $command)
    {
        $this->commands[$command->getSignature()] = $command;
    }

    public function run(array $argv)
    {
        $command = $argv[1] ?? $this->defaultCommand;

        if ($command === '--help' || $command === '-h') {
            $this->showHelp();
            return;
        }

        if (!isset($this->commands[$command])) {
            $this->showError($command);
            return;
        }

        try {
            $this->commands[$command]->handle();
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
