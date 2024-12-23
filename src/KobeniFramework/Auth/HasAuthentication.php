<?php

namespace KobeniFramework\Auth;

trait HasAuthentication
{
    protected $auth;

    public function __construct()
    {
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct();
        }
        
        if (!$this->auth) {
            $this->auth = new AuthManager($this->db);
        }
    }
}