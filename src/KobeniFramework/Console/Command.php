<?php

namespace KobeniFramework\Console;

abstract class Command
{
    protected $signature;
    protected $description;

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