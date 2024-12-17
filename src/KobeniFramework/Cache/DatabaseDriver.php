<?php

namespace KobeniFramework\Cache;

use KobeniFramework\Database\DB;

class DatabaseDriver implements CacheDriver
{
    protected DB $db;
    protected string $table = 'cache';
    protected string $tagTable = 'cache_tags';

    public function __construct()
    {
        $this->db = new DB();
        $this->ensureTablesExist();
    }

    protected function ensureTablesExist(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `key` varchar(255) NOT NULL,
                `value` longtext NOT NULL,
                `expiration` int unsigned NOT NULL,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->tagTable}` (
                `tag` varchar(255) NOT NULL,
                `key` varchar(255) NOT NULL,
                KEY `cache_tags_key_index` (`key`),
                KEY `cache_tags_tag_index` (`tag`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function get(string $key): mixed
    {
        $result = $this->db->query(
            "SELECT `value`, `expiration` FROM `{$this->table}` WHERE `key` = ?",
            [$key]
        );

        if (empty($result)) {
            return null;
        }

        $item = $result[0];

        if ($item['expiration'] !== 0 && $item['expiration'] < time()) {
            $this->forget($key);
            return null;
        }

        return unserialize($item['value']);
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        $value = serialize($value);
        $expiration = $ttl > 0 ? time() + $ttl : 0;

        return $this->db->query(
            "REPLACE INTO `{$this->table}` (`key`, `value`, `expiration`) VALUES (?, ?, ?)",
            [$key, $value, $expiration]
        ) !== false;
    }

    public function forget(string $key): bool
    {
        $this->db->query(
            "DELETE FROM `{$this->tagTable}` WHERE `key` = ?",
            [$key]
        );

        return $this->db->query(
            "DELETE FROM `{$this->table}` WHERE `key` = ?",
            [$key]
        ) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        if (!empty($tags)) {
            $this->tagItem($key, $tags);
        }

        return $value;
    }

    protected function tagItem(string $key, array $tags): void
    {
        $this->db->query(
            "DELETE FROM `{$this->tagTable}` WHERE `key` = ?",
            [$key]
        );

        foreach ($tags as $tag) {
            $this->db->query(
                "INSERT INTO `{$this->tagTable}` (`tag`, `key`) VALUES (?, ?)",
                [$tag, $key]
            );
        }
    }

    public function forgetByTags(array $tags): bool
    {
        try {
            $this->db->beginTransaction();

            $placeholders = str_repeat('?,', count($tags) - 1) . '?';
            $keys = $this->db->query(
                "SELECT DISTINCT `key` FROM `{$this->tagTable}` WHERE `tag` IN ({$placeholders})",
                $tags
            );

            if (!empty($keys)) {
                $keyValues = array_column($keys, 'key');
                $keyPlaceholders = str_repeat('?,', count($keyValues) - 1) . '?';

                $this->db->query(
                    "DELETE FROM `{$this->tagTable}` WHERE `key` IN ({$keyPlaceholders})",
                    $keyValues
                );

                $this->db->query(
                    "DELETE FROM `{$this->table}` WHERE `key` IN ({$keyPlaceholders})",
                    $keyValues
                );
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function flush(): bool
    {
        try {
            $this->db->beginTransaction();
            $this->db->query("TRUNCATE `{$this->tagTable}`");
            $this->db->query("TRUNCATE `{$this->table}`");
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
