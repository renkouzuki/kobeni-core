<?php

namespace KobeniFramework\Auth\Middleware;

use KobeniFramework\Auth\AuthManager;
use KobeniFramework\Auth\Exceptions\AuthenticationException;

class Authenticate
{
    protected AuthManager $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, $next)
    {
        if (!$this->auth->check()) {
            throw new AuthenticationException(
                'Unauthenticated.', 
                [$this->auth->shouldUseTokens() ? 'token' : 'session']
            );
        }

        return $next($request);
    }
}