<?php

namespace KobeniFramework\Console\Commands;

use KobeniFramework\Console\Command;
use KobeniFramework\Environment\EnvLoader;

class StartCommand extends Command
{
    protected $signature = 'start';
    protected $description = 'Start the development server';

    public function handle()
    {
        EnvLoader::load(__DIR__ . '/../../../../.env');

        $host = 'localhost';

        $port = getenv('SERVER_PORT') ?: 8000;

        echo "\033[32mKobeni Framework Development Server\033[0m\n\n";
        echo "Starting server at \033[36mhttp://$host:$port\033[0m\n";
        echo "Press Ctrl+C to stop the server\n\n";

        exec("php -S $host:$port -t public");
    }
}