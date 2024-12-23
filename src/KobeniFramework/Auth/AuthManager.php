<?php

namespace KobeniFramework\Auth;

use KobeniFramework\Auth\Guards\AbstractGuard;

class AuthManager extends AbstractGuard
{
    protected string $defaultGuard = 'session';
    protected array $guards = [];

    public function __construct($db)
    {
        $this->db = $db;
        $this->config = $this->loadConfig();
    }

    protected function getDefaultConfig(): array
    {
        return [
            'token_lifetime' => 3600,
            'refresh_token_lifetime' => 604800,
            'session_lifetime' => 7200,
            'token_prefix' => 'kbn_',
            'session_name' => 'kbn_session',
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? null
        ];
    }

    public function guard($name = null)
    {
        $name = $name ?: $this->defaultGuard;

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->createGuard($name);
        }

        return $this->guards[$name];
    }

    protected function createGuard($name)
    {
        return match ($name) {
            'token' => new Guards\TokenGuard($this->db),
            default => new Guards\SessionGuard($this->db)
        };
    }

    public function shouldUseTokens()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        return strpos($authHeader, 'Bearer') !== false;
    }

    public function attempt(array $credentials)
    {
        return $this->guard($this->shouldUseTokens() ? 'token' : 'session')
            ->attempt($credentials);
    }

    public function user()
    {
        return $this->guard($this->shouldUseTokens() ? 'token' : 'session')
            ->user();
    }

    public function check(): bool
    {
        return $this->guard($this->shouldUseTokens() ? 'token' : 'session')
            ->check();
    }

    public function logout(): void
    {
        $this->guard($this->shouldUseTokens() ? 'token' : 'session')
            ->logout();
    }

    public function validate(array $credentials): bool
    {
        return $this->guard($this->shouldUseTokens() ? 'token' : 'session')
            ->validate($credentials);
    }

    public function refresh($token = null)
    {
        return $this->guard('token')->refresh($token);
    }

    protected function retrieveById($id)
    {
        return $this->guard(
            $this->shouldUseTokens() ? 'token' : 'session'
        )->retrieveById($id);
    }

    protected function retrieveByCredentials(array $credentials)
    {
        return $this->guard(
            $this->shouldUseTokens() ? 'token' : 'session'
        )->retrieveByCredentials($credentials);
    }

    protected function validateCredentials($user, array $credentials): bool
    {
        return $this->guard(
            $this->shouldUseTokens() ? 'token' : 'session'
        )->validateCredentials($user, $credentials);
    }
}
