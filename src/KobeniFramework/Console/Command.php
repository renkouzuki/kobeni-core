<?php

namespace KobeniFramework\Console;

abstract class Command
{
    protected $signature;
    protected $description;
    protected $arguments = []; 

    public function __construct()
    {
        $this->parseSignature(); 
    }

    protected function parseSignature()  // Add this method
    {
        $parts = explode(' ', $this->signature);

        foreach ($parts as $i => $part) {
            if ($i === 0) continue;

            if (preg_match('/\{(\w+)\}/', $part, $matches)) {
                $this->arguments[$matches[1]] = null;
            }
        }
    }

    public function argument($key)  // Add this method
    {
        return $this->arguments[$key] ?? null;
    }

    public function setArgument($key, $value)  // Add this method
    {
        $this->arguments[$key] = $value;
    }

    public function info($message)  // Add this method
    {
        echo "\033[32m$message\033[0m\n";
    }

    public function error($message)  // Add this method
    {
        echo "\033[31m$message\033[0m\n";
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
}
