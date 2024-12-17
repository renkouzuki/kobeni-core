<?php

namespace KobeniFramework\Controllers;

use KobeniFramework\Controllers\RequestDataMixing\MixedAccessData;
use KobeniFramework\Database\DB;
use KobeniFramework\Http\Response;
use KobeniFramework\View\View;

abstract class Controller
{
    protected $db;

    public function __construct()
    {
        if ($this->needsDatabase()) {
            $this->db = DB::getInstance();
        }
    }

    protected function getRequestData()
    {
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $data = json_decode(file_get_contents('php://input'), true);
            return new MixedAccessData($data); 
        }

        return new MixedAccessData($_POST);
    }

    protected function redirect($path)
    {
        header("Location: $path");
        exit;
    }

    protected function needsDatabase()
    {
        return true;
    }

    protected function view($view, $data = [])
    {
        return View::make($view, $data);
    }

    protected function json($data, $status = 200)
    {
        return Response::json($data, $status);
    }
}
