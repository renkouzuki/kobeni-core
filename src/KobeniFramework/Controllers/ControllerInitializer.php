<?php

namespace KobeniFramework\Controllers;

use KobeniFramework\Database\DB;
use KobeniFramework\Database\Kobeni;

trait ControllerInitializer 
{
    protected function initializeController() 
    {
        if ($this->needsDatabase()) {
            $this->db = DB::getInstance();
        }
        $this->kobeni = new Kobeni();
        $this->req = $this->getRequestData();
    }
}