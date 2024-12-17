<?php

namespace KobeniFramework\Cache;

interface CacheDriver
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value, int $ttl): bool;
    public function forget(string $key): bool;
    public function has(string $key): bool;
    public function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed;
    public function forgetByTags(array $tags): bool;
    public function flush(): bool;
}