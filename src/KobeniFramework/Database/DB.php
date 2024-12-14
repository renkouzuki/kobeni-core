<?php

namespace KobeniFramework\Database;

use PDO;
use KobeniFramework\Routing\Router;

class DB
{
    protected static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            $router = new Router();
            self::$instance = $router->connectDatabase();
        }
        return self::$instance;
    }
}