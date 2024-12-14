<?php

namespace KobeniFramework\Providers;

use KobeniFramework\Foundation\Application;

abstract class ServiceProvider
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    abstract public function register();
}