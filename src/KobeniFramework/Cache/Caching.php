<?php

namespace KobeniFramework\Cache;

class Caching
{
    protected static ?Cache $instance = null;

    protected static function getInstance(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new Cache();
        }
        return self::$instance;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return static::getInstance()->$name(...$arguments);
    }
}