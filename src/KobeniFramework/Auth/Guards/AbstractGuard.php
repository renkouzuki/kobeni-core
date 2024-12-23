<?php

namespace KobeniFramework\Auth\Guards;

use KobeniFramework\Auth\Contracts\Guard as GuardContract;

abstract class AbstractGuard implements GuardContract
{
    protected $config;
    protected $db;
    protected $user = null;

    protected function loadConfig(): array
    {
        $possiblePaths = [
            getcwd() . '/config/Auth.php',
            dirname(getcwd()) . '/config/Auth.php',
            __DIR__ . '/../../../../config/Auth.php'
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $config = require $path;
                if (is_array($config)) {
                    return array_merge($this->getDefaultConfig(), $config);
                }
            }
        }

        return $this->getDefaultConfig();
    }

    abstract protected function getDefaultConfig(): array;
    abstract protected function retrieveById($id);
    abstract protected function retrieveByCredentials(array $credentials);
    abstract protected function validateCredentials($user, array $credentials): bool;

    public function user()
    {
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    public function validate(array $credentials): bool
    {
        $user = $this->retrieveByCredentials($credentials);
        return $user && $this->validateCredentials($user, $credentials);
    }
}