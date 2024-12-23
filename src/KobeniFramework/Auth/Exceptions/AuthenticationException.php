<?php

namespace KobeniFramework\Auth\Exceptions;

use Exception;

class AuthenticationException extends Exception {
    
    protected array $guards;

    public function __construct(string $message = "Unauthenticated." , array $guards = [] , protected mixed $redirectTo = null) {
        parent::__construct($message,401);
        $this->guards = $guards;
    }

    public function guards(): array{
        return $this->guards();
    }

    public function redirectTo(): ?string{
        return $this->redirectTo();
    }
}