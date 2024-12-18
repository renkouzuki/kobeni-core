<?php

namespace KobeniFramework\Controllers;

use KobeniFramework\Controllers\RequestDataMixing\MixedAccessData;
use KobeniFramework\Database\DB;
use KobeniFramework\Http\Response;
use KobeniFramework\Validation\Validator;
use KobeniFramework\View\View;

abstract class Controller
{
    protected $req;
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

    protected function validate(array $rules): MixedAccessData
    {
        $this->req = $this->req ?? $this->getRequestData();
        
        $validator = Validator::make($this->req);
        
        if (!$validator->validate($rules)) {
            return $this->json([
                'status' => false,
                'errors' => $validator->getErrors()
            ], 422);
            exit;
        }

        return $this->req;
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
