<?php

namespace KobeniFramework\Cache;

use KobeniFramework\Cache\Exceptions\CacheException;

class Lock
{
    protected string $key;
    protected string $owner;

    public function __construct(
        protected CacheDriver $driver,
        protected string $name,
        protected int $seconds = 60
    ) {
        $this->key = "lock:{$name}";
        $this->owner = uniqid('', true);
    }

    public function get(): bool
    {
        if ($this->driver->has($this->key)) {
            return false;
        }

        return $this->driver->put($this->key, $this->owner, $this->seconds);
    }

    public function block(int $timeout = 60): bool
    {
        $start = time();

        while (!$this->get()) {
            if (time() - $start >= $timeout) {
                throw new CacheException("Could not acquire lock: {$this->name}");
            }

            usleep(250000); 
        }

        return true;
    }

    public function release(): bool
    {
        // Only release if we own the lock
        if ($this->driver->get($this->key) === $this->owner) {
            return $this->driver->forget($this->key);
        }

        return false;
    }
}