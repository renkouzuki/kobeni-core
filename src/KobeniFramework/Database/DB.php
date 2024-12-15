<?php

namespace KobeniFramework\Database;

use PDO;
use KobeniFramework\Routing\Router;
use PDOException;

class DB
{
    protected static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    protected static function createConnection(): PDO
    {
        $config = self::loadConfig();

        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s",
            $config['DB_HOST'],
            $config['DB_PORT'],
            $config['DB_DATABASE']
        );

        try {
            return new PDO(
                $dsn,
                $config['DB_USERNAME'],
                $config['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    protected static function loadConfig(): array
    {
        $projectRoot = dirname(getcwd());
        $configPath = $projectRoot . '/config/Database.php';

        if (!file_exists($configPath)) {
            throw new \RuntimeException('Database configuration not found');
        }

        $config = require $configPath;

        if (!$config) {
            throw new \RuntimeException('Invalid database configuration');
        }

        return $config;
    }

    public function query(string $sql, array $params = []): mixed
    {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);

            if (stripos($sql, 'SELECT') === 0) {
                return $stmt->fetchAll();
            }

            if (stripos($sql, 'INSERT') === 0) {
                return self::getInstance()->lastInsertId();
            }

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                "Query execution failed: " . $e->getMessage()
            );
        }
    }

    public function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    public function commit(): void
    {
        self::getInstance()->commit();
    }

    public function rollBack(): void
    {
        self::getInstance()->rollBack();
    }
}
