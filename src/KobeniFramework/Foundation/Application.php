<?php

namespace KobeniFramework\Foundation;

use KobeniFramework\Environment\EnvLoader;
use KobeniFramework\Routing\Router;

class Application
{
    protected $basePath;
    protected $router;
    protected $providers = [];
    protected $config = [];

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->router = new Router($this);
        $this->loadConfigurations();
    }

    protected function loadConfigurations()
    {
        // Load all configuration files from the config directory
        $configPath = $this->basePath . '/config';
        if (is_dir($configPath)) {
            foreach (glob($configPath . '/*.php') as $configFile) {
                $key = basename($configFile, '.php');
                $this->config[$key] = require $configFile;
            }
        }
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    public function initialize()
    {
        $this->loadEnvironment();
        $this->registerProviders();
        $this->bootProviders();
    }

    protected function loadEnvironment()
    {
        EnvLoader::load($this->basePath . '/.env');
    }

    public function registerProviders()
    {
        $providers = $this->getConfig('providers') ?? [];
        foreach ($providers as $provider) {
            $providerInstance = new $provider($this);
            $providerInstance->register();
            $this->providers[] = $providerInstance;
        }
    }

    protected function bootProviders()
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }
}
