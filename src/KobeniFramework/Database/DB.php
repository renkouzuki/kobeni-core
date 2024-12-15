<?php

namespace KobeniFramework\Database;

use KobeniFramework\Environment\EnvLoader;
use PDO;
use KobeniFramework\Routing\Router;
use PDOException;

class DB
{
    protected static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::loadEnvironment();
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    protected static function loadEnvironment(): void
    {
        $paths = [
            getcwd() . '/.env',
            dirname(getcwd()) . '/.env',
            __DIR__ . '/../../../../.env'
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                EnvLoader::load($path);
                break;
            }
        }
    }

    protected static function createConnection(): PDO
    {
        $config = self::loadConfig();

        echo "Using database configuration:\n";
        echo "Host: " . $config['DB_HOST'] . "\n";
        echo "Database: " . $config['DB_DATABASE'] . "\n";

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
        $possiblePaths = [
            getcwd() . '/config/Database.php',
            dirname(getcwd()) . '/config/Database.php',
            __DIR__ . '/../../../../config/Database.php'
        ];

        echo "Looking for config file in:\n";
        foreach ($possiblePaths as $path) {
            echo "- $path" . (file_exists($path) ? " (Found!)" : " (Not found)") . "\n";
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $config = require $path;
                if (is_array($config)) {
                    return $config;
                }
            }
        }

        throw new \RuntimeException('Database configuration not found in any of the expected locations');
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
