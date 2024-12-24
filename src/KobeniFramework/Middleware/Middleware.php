<?php

namespace KobeniFramework\Middleware;

use KobeniFramework\Auth\AuthManager;
use KobeniFramework\Database\DB;
use KobeniFramework\Http\Response;

abstract class Middleware
{
    protected $db;
    protected $auth;

    public function __construct()
    {
        if($this->needsDatabase()){
            $this->db = DB::getInstance();
            $this->auth = new AuthManager($this->db);
        }
    }

    abstract public function handle($next);

    protected function needsDatabase()
    {
        return true;
    }

    protected function json($data, $status = 200)
    {
        return Response::json($data, $status);
    }

    protected function redirect($path)
    {
        header("Location: $path");
        exit;
    }

    protected function getRequestData()
    {
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            return json_decode(file_get_contents('php://input'), true);
        }
        return $_POST;
    }
}
