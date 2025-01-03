<?php

namespace KobeniFramework\Routing;

use KobeniFramework\Database\DB;
use PDOException;
use KobeniFramework\Foundation\Application;

class Router
{
    protected $routes = [];
    protected static $pdo = null;
    protected $app;
    protected $middlewareGroups = [];
    protected $currentGroup = null;
    protected $parameters = [];

    public function __construct(Application $app = null)
    {
        $this->app = $app;
    }

    public function get($route, $action)
    {
        return $this->addRoute('GET', $route, $action);
    }

    public function post($route, $action)
    {
        return $this->addRoute('POST', $route, $action);
    }

    public function put($route, $action)
    {
        return $this->addRoute('PUT', $route, $action);
    }

    public function delete($route, $action)
    {
        return $this->addRoute('DELETE', $route, $action);
    }

    public function addRoute($method, $route, $action)
    {
        $route = trim($route, '/');

        if ($this->currentGroup && isset($this->currentGroup['middleware'])) {
            if (is_string($action)) {
                $action = [
                    'uses' => $action,
                    'middleware' => $this->currentGroup['middleware']
                ];
            }
        }

        if ($this->currentGroup) {
            if (isset($this->currentGroup['prefix'])) {
                $route = trim($this->currentGroup['prefix'], '/') . '/' . $route;
            }

            if (isset($this->currentGroup['middleware'])) {
                if (is_array($action)) {
                    if (!isset($action['middleware'])) {
                        $action['middleware'] = [];
                    }
                    $action['middleware'] = array_merge(
                        $action['middleware'],
                        $this->currentGroup['middleware']
                    );
                }
            }
        }

        $this->routes[] = new Route($method, $route, $action);
        return $this;
    }

    public function dispatch($method, $uri)
    {
        $uri = trim($uri, '/');

        // echo "Requested Method: " . $method . "\n";
        // echo "Requested URI: " . $uri . "\n";

        foreach ($this->routes as $route) {
            // echo "Comparing with route: " . $route->getMethod() . " " . $route->getRoute() . "\n";

            if ($this->matchRoute($route, $method, $uri)) {
                return $this->handleRoute($route);
            }
        }

        $this->handleNotFound();
    }

    protected function matchRoute($route, $method, $uri)
    {
        if ($route->getMethod() != strtoupper($method)) {
            return false;
        }

        $pattern = $this->createPatternFromRoute($route->getRoute());

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            $this->parameters = $matches;
            $route->setParameters($matches);
            return true;
        }

        return false;
    }

    protected function createPatternFromRoute($route)
    {
        return '#^' . preg_replace('/\{([a-zA-Z]+)\}/', '([^/]+)', $route) . '$#';
    }

    protected function handleRoute($route)
    {
        try {
            $response = $this->runMiddleware(
                $route,
                function () use ($route) {
                    return $this->callAction($route->getAction(), $route->getParameters());
                }
            );

            $this->sendResponse($response);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    protected function runMiddleware($route, $callback)
    {
        if (is_array($route->getAction()) && isset($route->getAction()['middleware'])) {
            // var_dump('Checking middleware'); 
            // var_dump($route->getAction());   

            $middlewares = (array) $route->getAction()['middleware'];

            foreach ($middlewares as $middleware) {
                if ($middleware === 'Kobeni:Auth') {
                    $middlewareClass = "KobeniFramework\\Auth\\Middleware\\Authenticate";
                } else {
                    $middlewareClass = "App\\Middleware\\{$middleware}Middleware";
                }

                if (!class_exists($middlewareClass)) {
                    throw new \RuntimeException("Middleware {$middleware} not found: {$middlewareClass}");
                }

                if ($middlewareClass === "KobeniFramework\\Auth\\Middleware\\Authenticate") {
                    $db = DB::getInstance();
                    $auth = new \KobeniFramework\Auth\AuthManager($db);
                    $middlewareInstance = new $middlewareClass($auth);
                } else {
                    $middlewareInstance = new $middlewareClass();
                }

                return $middlewareInstance->handle($callback);
            }
        }

        return $callback();
    }

    public function connectDatabase()
    {
        try {
            return DB::getInstance();
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    protected function callAction($action, $parameters = [])
    {
        if (is_callable($action)) {
            return call_user_func_array($action, $parameters);
        }

        if (is_array($action) && isset($action['uses'])) {
            $action = $action['uses'];
        }

        list($controller, $method) = explode('@', $action);
        $controllerInstance = new $controller();
        return call_user_func_array([$controllerInstance, $method], $parameters);
    }

    protected function handleNotFound()
    {
        header("HTTP/1.1 404 Not Found");
        $this->sendResponse(json_encode(["error" => "404 Not Found"]));
    }

    protected function handleException(\Exception $e)
    {
        header("HTTP/1.1 500 Internal Server Error");
        $this->sendResponse(json_encode([
            "error" => "Internal Server Error",
            "message" => $e->getMessage()
        ]));
    }

    protected function sendResponse($response)
    {
        if (!headers_sent()) {
            if (strpos($response, '<!DOCTYPE html>') !== false || strpos($response, '<html') !== false) {
                header('Content-Type: text/html');
            } else {
                header('Content-Type: application/json');
            }
        }
        echo $response;
        exit();
    }

    public function group($attributes, $callback)
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = $attributes;

        call_user_func($callback, $this);

        $this->currentGroup = $previousGroup;
        return $this;
    }
}
