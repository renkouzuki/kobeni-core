<?php

namespace KobeniFramework\Auth\Guards;

use KobeniFramework\Auth\Exceptions\AuthenticationException;
use PDO;

class SessionGuard extends AbstractGuard
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->config = $this->loadConfig();
        $this->startSession();
        $this->loadUserFromSession();
    }

    protected function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config['session_name']);
            session_start([
                'cookie_lifetime' => $this->config['session_lifetime'],
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]);
        }
    }

    protected function loadUserFromSession()
    {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->retrieveById($_SESSION['user_id']);
        }
    }

    public function attempt(array $credentials)
    {
        if ($this->validate($credentials)) {
            $user = $this->retrieveByCredentials($credentials);
            $this->login($user);
            return true;
        }
        
        throw new AuthenticationException('Invalid credentials');
    }

    public function login($user)
    {
        $this->user = $user;
        $_SESSION['user_id'] = $user->id;
        session_regenerate_id(true);
    }

    public function logout(): void
    {
        $this->user = null;
        session_unset();
        session_destroy();
    }

    protected function getDefaultConfig(): array
    {
        return [
            'session_lifetime' => 7200,
            'session_name' => 'kbn_session'
        ];
    }

    protected function retrieveById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    protected function retrieveByCredentials(array $credentials)
    {
        $query = array_filter($credentials, fn($key) => $key !== 'password', ARRAY_FILTER_USE_KEY);
        $sql = "SELECT * FROM user WHERE " . implode(' AND ', array_map(fn($field) => "$field = ?", array_keys($query)));
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($query));
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        return $result;
    }

    protected function validateCredentials($user, array $credentials): bool
    {
        return password_verify($credentials['password'], $user->password);
    }
}