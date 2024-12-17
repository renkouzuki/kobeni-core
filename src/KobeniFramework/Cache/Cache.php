<?php

namespace KobeniFramework\Cache;

class Cache
{
    protected CacheDriver $driver;

    public function __construct()
    {
        $this->driver = new DatabaseDriver();
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->driver->remember($key, $ttl, $callback);
    }

    public function get(string $key): mixed
    {
        return $this->driver->get($key);
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        return $this->driver->put($key, $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return $this->driver->forget($key);
    }

    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }

    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this->driver, $tags);
    }

    public function flush(): bool
    {
        return $this->driver->flush();
    }
}
