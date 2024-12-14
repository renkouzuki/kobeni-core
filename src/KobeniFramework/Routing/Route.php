<?php
// src/KobeniFramework/Routing/Route.php
namespace KobeniFramework\Routing;

class Route
{
    protected $method;
    protected $route;
    protected $action;
    protected $parameters = [];

    public function __construct($method, $route, $action)
    {
        $this->method = strtoupper($method);
        $this->route = trim($route, '/');
        $this->action = $action;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
