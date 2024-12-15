<?php

namespace KobeniFramework\Console;

abstract class Command
{
    protected $signature;
    protected $description;
    protected $arguments = [];
    protected $options = [];

    public function __construct()
    {
        $this->parseSignature();
    }

    abstract public function handle();

    public function getSignature()
    {
        return $this->signature;
    }

    public function getDescription()
    {
        return $this->description;
    }

    protected function parseSignature()
    {
        if (empty($this->signature)) {
            return;
        }

        $parts = explode(' ', $this->signature);
        $command = array_shift($parts);

        foreach ($parts as $part) {
            if (preg_match('/\{([^\?}]+)\??\}/', $part, $matches)) {
                $this->arguments[$matches[1]] = null;
            }
        }
    }

    public function setArgument($name, $value)
    {
        if (isset($this->arguments[$name])) {
            $this->arguments[$name] = $value;
        }
    }

    protected function argument($key)
    {
        return $this->arguments[$key] ?? null;
    }

    protected function info($string)
    {
        echo "\033[32m$string\033[0m\n";
    }

    protected function error($string)
    {
        echo "\033[31m$string\033[0m\n";
    }
}
