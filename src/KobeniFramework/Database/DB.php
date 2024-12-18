<?php

namespace KobeniFramework\Database;

use KobeniFramework\Environment\EnvLoader;
use PDO;
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

    protected static function loadConfig(): array
    {
        $possiblePaths = [
            getcwd() . '/config/Database.php',
            dirname(getcwd()) . '/config/Database.php',
            __DIR__ . '/../../../../config/Database.php'
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $config = require $path;
                if (is_array($config)) {
                    return $config;
                }
            }
        }

        throw new \RuntimeException('Database configuration not found in any of the expected locations' , 404);
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
            throw new PDOException("Database connection failed: " . $e->getMessage() , $e->getCode());
        }
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
                "Query execution failed: " . $e->getMessage() , $e->getCode()
            );
        }
    }

    public function beginTransaction(): bool
    {
        if (!self::getInstance()->inTransaction()) {
            return self::getInstance()->beginTransaction();
        }
        return true;
    }

    public function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }

    public function commit(): bool
    {
        if (self::getInstance()->inTransaction()) {
            return self::getInstance()->commit();
        }
        return true;
    }

    public function rollBack(): bool
    {
        if (self::getInstance()->inTransaction()) {
            return self::getInstance()->rollBack();
        }
        return true;
    }
}