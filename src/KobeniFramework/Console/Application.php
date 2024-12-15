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
        $signature = explode(' ', $command->getSignature())[0];
        $this->commands[$signature] = $command;
    }

    public function run(array $argv)
    {
        $commandName = $argv[1] ?? $this->defaultCommand;

        if ($commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return;
        }

        $command = $this->findCommand($commandName);
        if (!$command) {
            $this->showError($commandName);
            return;
        }

        try {
            $this->handleCommandArguments($command, array_slice($argv, 2));
            $command->handle();
        } catch (\Exception $e) {
            echo "\033[31mError: {$e->getMessage()}\033[0m\n";
        }
    }

    protected function findCommand($name)
    {
        // Handle commands with colons (e.g., migration:generate)
        foreach ($this->commands as $signature => $command) {
            if (strpos($signature, $name) === 0) {
                return $command;
            }
        }
        return null;
    }

    protected function handleCommandArguments(Command $command, array $args)
    {
        $signature = $command->getSignature();
        preg_match_all('/\{([^\?}]+)\??\}/', $signature, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $argumentName) {
                if (isset($args[$index])) {
                    $command->setArgument($argumentName, $args[$index]);
                }
            }
        }
    }

    protected function showHelp()
    {
        echo "\033[32mKobeni Framework\033[0m\n\n";
        echo "Usage:\n";
        echo "  php kobeni [command] [options]\n\n";
        echo "Available commands:\n";

        foreach ($this->commands as $signature => $command) {
            echo sprintf("  \033[36m%-30s\033[0m %s\n", $command->getSignature(), $command->getDescription());
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
