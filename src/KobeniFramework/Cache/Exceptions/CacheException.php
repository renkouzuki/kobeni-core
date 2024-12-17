<?php

namespace KobeniFramework\Cache\Exceptions;

class CacheException extends \Exception
{
    public static function lockTimeout(string $name): self
    {
        return new self("Lock timeout exceeded for: {$name}");
    }

    public static function invalidTag(string $tag): self
    {
        return new self("Invalid cache tag: {$tag}");
    }

    public static function databaseError(string $message): self
    {
        return new self("Cache database error: {$message}");
    }
}