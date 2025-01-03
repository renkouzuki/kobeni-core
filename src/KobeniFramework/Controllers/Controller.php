<?php

namespace KobeniFramework\Controllers;

use KobeniFramework\Auth\AuthManager;
use KobeniFramework\Controllers\RequestDataMixing\MixedAccessData;
use KobeniFramework\Database\DB;
use KobeniFramework\Http\Response;
use KobeniFramework\Validation\Validator;
use KobeniFramework\View\View;

abstract class Controller
{
    use ControllerInitializer;

    protected $req;
    protected $db;
    protected $auth;

    public function __construct() 
    {
        $this->initializeController();
        if($this->needsDatabase()){
            $this->db = DB::getInstance();
            $this->auth = new AuthManager($this->db);
        }
    }

    protected function getRequestData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        if ($contentType === 'application/json') {
            $data = json_decode(file_get_contents('php://input'), true);
            return new MixedAccessData($data);
        }

        return new MixedAccessData($_POST);
    }

    protected function validate(array $rules): MixedAccessData
    {
        if (!isset($this->req)) {
            $this->req = $this->getRequestData();
        }

        $validator = Validator::make($this->req);

        if (!$validator->validate($rules)) {
            throw new \Exception(json_encode($validator->getErrors()), 422);
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
