<?php

namespace KobeniFramework\Auth\Guards;

use KobeniFramework\Auth\JWT;
use KobeniFramework\Auth\Exceptions\{AuthenticationException, TokenException};
use PDO;

class TokenGuard extends AbstractGuard
{
    protected JWT $jwt;
    protected ?string $token = null;

    public function __construct($db)
    {
        $this->db = $db;
        $this->config = $this->loadConfig();
        $this->jwt = new JWT($this->config['jwt_secret'] ?? null);
    }

    public function attempt(array $credentials): array
    {
        if (!$this->validate($credentials)) {
            throw new AuthenticationException('Invalid credentials');
        }

        $user = $this->retrieveByCredentials($credentials);
        $this->setUser($user);

        return $this->issueTokens($user);
    }

    protected function issueTokens($user): array
    {
        $accessToken = $this->jwt->encode([
            'sub' => $user->id,
            'exp' => time() + $this->config['token_lifetime'],
            'type' => 'access'
        ]);

        $refreshToken = $this->jwt->encode([
            'sub' => $user->id,
            'exp' => time() + $this->config['refresh_token_lifetime'],
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(32))
        ]);

        $this->storeRefreshToken($user->id, $refreshToken);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->config['token_lifetime']
        ];
    }

    public function refresh(string $refreshToken): array
    {
        try {
            $payload = $this->jwt->decode($refreshToken);
            if ($payload['type'] !== 'refresh') {
                throw TokenException::invalid();
            }

            if (!$this->validateRefreshToken($refreshToken)) {
                throw TokenException::blacklisted();
            }

            $user = $this->retrieveById($payload['sub']);
            $this->revokeRefreshToken($refreshToken);

            return $this->issueTokens($user);
        } catch (\Exception $e) {
            throw TokenException::invalid();
        }
    }

    protected function getDefaultConfig(): array
    {
        return [
            'token_lifetime' => 3600,
            'refresh_token_lifetime' => 604800,
            'token_prefix' => 'kbn_',
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? null
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

    protected function storeRefreshToken($userId, $token): void
    {
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + $this->config['refresh_token_lifetime']);

        $stmt = $this->db->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $hash, $expires]);
    }

    protected function validateRefreshToken($token): bool
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare("SELECT * FROM refresh_tokens WHERE token = ? AND revoked = 0 AND expires_at > NOW()");
        $stmt->execute([$hash]);
        return $stmt->fetch() !== false;
    }

    protected function revokeRefreshToken($token): void
    {
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE token = ?");
        $stmt->execute([$hash]);
    }

    public function logout(): void
    {
        $this->user = null;
        $this->token = null;

        if ($token = $this->getTokenFromRequest()) {
            try {
                $payload = $this->jwt->decode($token);
                if (isset($payload['jti'])) {
                    $this->revokeRefreshToken($token);
                }
            } catch (\Exception $e) {
                // ignore token errors during logout
            }
        }
    }

    public function check(): bool
    {
        if ($this->user !== null) {
            return true;
        }
    
        if ($token = $this->getTokenFromRequest()) {
            try {
                $payload = $this->jwt->decode($token);
                
                if ($payload['type'] === 'refresh') {
                    if (!$this->validateRefreshToken($token)) {
                        return false;
                    }
                }
                
                if ($payload['type'] === 'access' && isset($payload['sub'])) {
                    if ($this->isUserTokensRevoked($payload['sub'])) {
                        return false;
                    }
                }
    
                $this->user = $this->retrieveById($payload['sub']);
                return $this->user !== null;
            } catch (\Exception $e) {
                return false;
            }
        }
    
        return false;
    }

    protected function getTokenFromRequest(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function isUserTokensRevoked(string $userId): bool
    {
        $stmt = $this->db->prepare("
        SELECT EXISTS(
            SELECT 1 
            FROM refresh_tokens 
            WHERE user_id = ? 
              AND revoked = 0 
              AND expires_at > NOW()
        ) as has_valid_token
    ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return !$result['has_valid_token'];
    }
}
