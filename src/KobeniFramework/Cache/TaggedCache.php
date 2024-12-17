<?php

namespace KobeniFramework\Cache;

class TaggedCache
{
    public function __construct(
        protected CacheDriver $driver,
        protected array $tags
    ) {}

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->driver->remember($key, $ttl, $callback, $this->tags);
    }

    public function forget(): bool
    {
        return $this->driver->forgetByTags($this->tags);
    }

    public function flush(): bool
    {
        return $this->forget();
    }
}