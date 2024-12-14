<?php

namespace KobeniFramework\Environment;

class EnvLoader
{
    public static function load($path)
    {
        $possiblePaths = [
            $path,
            __DIR__ . '/../../../../.env',
            getcwd() . '/.env'
        ];

        $envFile = null;
        foreach ($possiblePaths as $possiblePath) {
            if (file_exists($possiblePath)) {
                $envFile = $possiblePath;
                break;
            }
        }

        if (!$envFile) {
            throw new \RuntimeException('.env file not found. Please copy .env.example to .env and configure your settings.');
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(str_replace(['"', "'"], '', $value));
                
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}