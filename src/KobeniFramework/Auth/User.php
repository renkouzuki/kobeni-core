<?php

namespace KobeniFramework\Auth;

trait Authenticatable
{
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getRememberToken(): string
    {
        return $this->{$this->getRememberTokenName()};
    }

    public function setRememberToken($value): void
    {
        $this->{$this->getRememberTokenName()} = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}