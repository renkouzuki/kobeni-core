<?php

namespace KobeniFramework\Middleware;

use KobeniFramework\Database\DB;
use KobeniFramework\Http\Response;

abstract class Middleware
{
    protected $db;

    public function __construct()
    {
        if ($this->needsDatabase()) {
            $this->db = DB::getInstance();
        }
    }

    abstract public function handle($next);

    protected function needsDatabase()
    {
        return false;
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
