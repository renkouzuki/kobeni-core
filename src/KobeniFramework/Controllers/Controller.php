<?php

namespace KobeniFramework\Controllers;

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