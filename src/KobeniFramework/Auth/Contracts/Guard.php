<?php

namespace KobeniFramework\Auth\Contracts;

interface Guard {
    public function attempt(array $credentials);
    public function user();
    public function check():bool;
    public function logout():void;
    public function validate(array $credentials):bool;
    public function setUser($user):self;
}