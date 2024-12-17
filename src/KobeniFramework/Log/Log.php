<?php

namespace KobeniFramework\Log;

class Log
{
    protected static ?Logger $instance = null;

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            $rootPath = dirname(getcwd());
            self::$instance = new Logger($rootPath);
        }
        return self::$instance;
    }

    public static function __callStatic(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }

    /// this one is a helper
    public static function debug($message, array $context = [])
    {
        return self::getInstance()->debug($message, $context);
    }

    public static function info($message, array $context = [])
    {
        return self::getInstance()->info($message, $context);
    }

    public static function warning($message, array $context = [])
    {
        return self::getInstance()->warning($message, $context);
    }

    public static function error($message, array $context = [])
    {
        return self::getInstance()->error($message, $context);
    }
}