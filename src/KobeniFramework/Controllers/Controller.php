<?php

namespace KobeniFramework\Controllers;

use KobeniFramework\Controllers\RequestDataMixing\MixedAccessData;
use KobeniFramework\Http\Response;
use KobeniFramework\Validation\Validator;
use KobeniFramework\View\View;

abstract class Controller
{
    use ControllerInitializer;

    protected $req;
    protected $db;

    public function __construct() 
    {
        $this->initializeController();
    }

    protected function getRequestData()
    {
        var_dump("it triggered error");

        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
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
