<?php

namespace KobeniFramework\Database;

use KobeniFramework\Database\Schema\ModelBuilder;
use KobeniFramework\Database\Schema\Schema;

class Kobeni
{
    protected DB $db;
    protected Schema $schema;
    protected array $relationships = [];

    public function __construct()
    {
        $this->db = new DB();
        $this->loadSchema();
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    protected function loadSchema(): void
    {
        $schemaPath = getcwd() . '/database/schema.php';
        if (!file_exists($schemaPath)) {
            $schemaPath = dirname(getcwd()) . '/database/schema.php';
        }

        if (file_exists($schemaPath)) {
            $modelDefinitions = require $schemaPath;

            foreach ($modelDefinitions as $modelName => $callback) {
                $modelBuilder = new ModelBuilder($modelName);
                $callback($modelBuilder);
                $definition = $modelBuilder->getDefinition();

                if (isset($definition['relationships'])) {
                    $tableName = strtolower(rtrim($modelName, 's'));
                    $this->relationships[$tableName] = [];

                    foreach ($definition['relationships'] as $relation) {
                        $targetModel = rtrim($relation['model'], 's');
                        $targetTable = strtolower($targetModel);

                        switch ($relation['type']) {
                            case 'belongsTo':
                                $this->relationships[$tableName][$targetTable] = [
                                    'type' => 'belongsTo',
                                    'model' => $targetTable,
                                    'foreignKey' => strtolower($targetTable) . '_id',
                                    'ownerKey' => 'id'
                                ];
                                break;

                            case 'hasMany':
                                $this->relationships[$tableName][$targetTable] = [
                                    'type' => 'hasMany',
                                    'model' => $targetTable,
                                    'foreignKey' => strtolower($tableName) . '_id',
                                    'localKey' => 'id'
                                ];
                                break;

                            case 'belongsToMany':
                                $this->relationships[$tableName][$targetTable] = [
                                    'type' => 'belongsToMany',
                                    'model' => $targetTable,
                                    'table' => $relation['table'],
                                    'foreignPivotKey' => strtolower($tableName) . '_id',
                                    'relatedPivotKey' => strtolower($targetTable) . '_id'
                                ];
                                break;
                        }
                    }
                }
            }

            // var_dump("Loaded relationships:", $this->relationships);
        }
    }

    protected function extractRelationships($schema): array
    {
        $relationships = [];
        foreach ($schema->getModels() as $modelName => $definition) {
            if (isset($definition['relationships'])) {
                $relationships[strtolower($modelName)] = $definition['relationships'];
            }
        }
        return $relationships;
    }

    public function create(string $table, $data, array $options = [])
    {
        $startedTransaction = false;

        try {
            if ((!empty($options['transaction'] ?? true)) && !$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // If there's no ID provided, generate a UUID
            if (!isset($data['id'])) {
                $data['id'] = $this->generateUuid();
            }

            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = sprintf(
                "INSERT INTO `%s` (%s) VALUES (%s)",
                $table,
                implode(', ', array_map(fn($f) => "`$f`", $fields)),
                implode(', ', $placeholders)
            );

            $this->db->query($sql, $values);
            $insertedId = $data['id']; // Use our generated/provided UUID

            if (!empty($options['include'])) {
                foreach ($options['include'] as $relation => $relationData) {
                    if (isset($this->relationships[$table][$relation])) {
                        $this->handleRelationshipCreate(
                            $table,
                            $insertedId,
                            $relation,
                            $relationData
                        );
                    }
                }
            }

            if ($startedTransaction) {
                $this->db->commit();
            }

            if (!empty($options['return']) && $options['return'] === true) {
                return $this->findUnique($table, ['id' => $insertedId], [
                    'include' => $options['include'] ?? []
                ]);
            }

            return $insertedId;
        } catch (\Exception $e) {
            if ($startedTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    protected function createMany(string $table, array $records, array $options = []): array
    {
        $results = [];

        try {
            if (!empty($options['transaction'] ?? true)) {
                $this->db->beginTransaction();
            }

            foreach ($records as $data) {
                $results[] = $this->create($table, $data, array_merge($options, [
                    'transaction' => false
                ]));
            }

            if (!empty($options['transaction'] ?? true)) {
                $this->db->commit();
            }

            // return created records if requested
            if (!empty($options['return']) && $options['return'] === true) {
                return $this->findMany($table, [
                    'id' => ['IN', $results]
                ], [
                    'include' => $options['include'] ?? []
                ]);
            }

            return $results;
        } catch (\Exception $e) {
            if (!empty($options['transaction'] ?? true)) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function findMany(string $table, array $where = [], array $options = []): array
    {
        $query = new QueryBuilder($this->db, $table);

        $this->applyQueryOptions($query, array_merge(['where' => $where], $options));

        $results = $query->get();

        if (!empty($options['include'])) {
            foreach ($results as &$result) {
                $result = $this->loadIncludes($table, $result, $options['include']);
            }
        }

        return $results;
    }

    public function findFirst(string $table, array $where = [], array $options = []): ?array
    {
        $query = new QueryBuilder($this->db, $table);

        // Ensure the table name is correctly formatted
        $table = rtrim(strtolower($table), 's');

        if (!empty($where)) {
            // Debug the where conditions
            // var_dump([
            //     'table' => $table,
            //     'where_conditions' => $where
            // ]);

            $query->where($where);
        }

        if (isset($options['select'])) {
            $query->select($options['select']);
        }

        $query->take(1);
        $results = $query->get();
        return $results[0] ?? null;
    }

    public function findUnique(string $table, array $where, array $options = []): array
    {
        $table = rtrim(strtolower($table), 's');

        $result = $this->findFirst($table, $where, $options);
        if ($result === null) {
            throw new \RuntimeException("Record not found in $table");
        }

        if (!empty($options['include'])) {
            $result = $this->loadIncludes($table, $result, $options['include']);
        }

        return $result;
    }

    protected function applyQueryOptions(QueryBuilder $query, array $options): void
    {
        if (!empty($options['where'])) {
            $query->where($options['where']);
        }

        if (isset($options['select'])) {
            $query->select($options['select']);
        }

        if (isset($options['orderBy'])) {
            if (is_array($options['orderBy'])) {
                foreach ($options['orderBy'] as $field => $direction) {
                    $query->orderBy($field, $direction);
                }
            } else {
                $query->orderBy($options['orderBy'][0], $options['orderBy'][1] ?? 'ASC');
            }
        }

        if (isset($options['take'])) {
            $query->take($options['take']);

            if (isset($options['skip'])) {
                $query->skip($options['skip']);
            }
        }
    }

    public function update(string $table, $where, array $data, array $options = [])
    {
        $startedTransaction = false;

        try {
            // Only start transaction if one isn't already active
            if ((!empty($options['transaction'] ?? true)) && !$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // Get current record data
            $currentRecord = $this->findUnique($table, is_array($where) ? $where : ['id' => $where]);

            // Merge current data with new data, only updating provided fields
            $finalData = array_merge(
                array_diff_key($currentRecord, ['id' => 1]), // Exclude ID from merging
                array_filter($data, fn($value) => $value !== null) // Only include non-null values
            );

            // Format where condition
            if (!is_array($where)) {
                $where = ['id' => $where];
            }

            $query = new QueryBuilder($this->db, $table);
            $updated = $query->where($where)->update($finalData);

            // Handle relationships if any
            if (!empty($options['include'])) {
                foreach ($options['include'] as $relation => $relationData) {
                    if (isset($this->relationships[$table][$relation])) {
                        $this->handleRelationshipUpdate(
                            $table,
                            $where['id'] ?? null,
                            $relation,
                            $relationData
                        );
                    }
                }
            }

            if ($startedTransaction) {
                $this->db->commit();
            }

            // Return updated record if requested
            if (!empty($options['return']) && $options['return'] === true) {
                return $this->findUnique($table, $where, [
                    'include' => $options['include'] ?? []
                ]);
            }

            return $updated;
        } catch (\Exception $e) {
            if ($startedTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(string $table, array $where): bool
    {
        $query = new QueryBuilder($this->db, $table);
        return $query->where($where)->delete();
    }

    protected function loadRelationships(string $table, array &$result, array $includes): void
    {
        foreach ($includes as $relationName => $options) {
            if (!isset($this->relationships[$table][$relationName])) {
                continue;
            }

            $relation = $this->relationships[$table][$relationName];
            $type = $relation['type'];
            $options = is_array($options) ? $options : [];

            $loader = new RelationLoader($this->db, $relation, $relationName, $this);
            $result[$relationName] = $loader->load($result, $options);

            // handle nested includes
            if (isset($options['include']) && !empty($result[$relationName])) {
                $relatedTable = $relation['model'];
                if (is_array($result[$relationName])) {
                    foreach ($result[$relationName] as &$relatedResult) {
                        $this->loadRelationships($relatedTable, $relatedResult, $options['include']);
                    }
                } else {
                    $this->loadRelationships($relatedTable, $result[$relationName], $options['include']);
                }
            }
        }
    }

    public function loadIncludes(string $table, array $data, array $includes): array
    {
        $table = rtrim(strtolower($table), 's');

        foreach ($includes as $relationName => $relationOptions) {
            $relationName = rtrim(strtolower($relationName), 's');

            if (isset($this->relationships[$table][$relationName])) {
                $relation = $this->relationships[$table][$relationName];
                $loader = new RelationLoader($this->db, $relation, $relationName, $this);
                $related = $loader->load($data, is_array($relationOptions) ? $relationOptions : []);

                if ($related !== null) {
                    // use plural form for hasMany/belongsToMany
                    $resultKey = $relation['type'] === 'hasMany' ? $relationName . 's' : $relationName;
                    $data[$resultKey] = $related;
                }
            }
        }

        return $data;
    }

    public function transaction(callable $callback)
    {
        $this->db->beginTransaction();
        try {
            $result = $callback($this);
            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    protected function handleRelationshipCreate(string $table, string $parentId, string $relation, array $data): void
    {
        $relationConfig = $this->relationships[$table][$relation];

        switch ($relationConfig['type']) {
            case 'hasOne':
            case 'hasMany':
                // add parent ID to related data
                $data = array_map(function ($item) use ($relationConfig, $parentId) {
                    $item[$relationConfig['foreignKey']] = $parentId;
                    return $item;
                }, is_array($data) ? $data : [$data]);

                $this->create($relationConfig['model'], $data, [
                    'transaction' => false
                ]);
                break;

            case 'belongsToMany':
                // create pivot table entries
                $pivotData = array_map(function ($item) use ($relationConfig, $parentId) {
                    return [
                        $relationConfig['foreignPivotKey'] => $parentId,
                        $relationConfig['relatedPivotKey'] => $item['id'] ?? $item
                    ];
                }, is_array($data) ? $data : [$data]);

                $this->create($relationConfig['table'], $pivotData, [
                    'transaction' => false
                ]);
                break;
        }
    }

    protected function handleRelationshipUpdate(string $table, ?string $parentId, string $relation, array $data): void
    {
        if (!$parentId) return;

        $relationConfig = $this->relationships[$table][$relation];

        switch ($relationConfig['type']) {
            case 'hasOne':
                $this->update(
                    $relationConfig['model'],
                    [$relationConfig['foreignKey'] => $parentId],
                    $data,
                    ['transaction' => false]
                );
                break;

            case 'hasMany':
                foreach ($data as $item) {
                    if (isset($item['id'])) {
                        $this->update(
                            $relationConfig['model'],
                            ['id' => $item['id']],
                            array_merge($item, [$relationConfig['foreignKey'] => $parentId]),
                            ['transaction' => false]
                        );
                    } else {
                        $this->create(
                            $relationConfig['model'],
                            array_merge($item, [$relationConfig['foreignKey'] => $parentId]),
                            ['transaction' => false]
                        );
                    }
                }
                break;

            case 'belongsToMany':
                // clear existing relationships if specified
                if (!empty($data['sync'])) {
                    $this->db->query(
                        "DELETE FROM {$relationConfig['table']} WHERE {$relationConfig['foreignPivotKey']} = ?",
                        [$parentId]
                    );
                }

                // add new relationships
                $pivotData = array_map(function ($item) use ($relationConfig, $parentId) {
                    return [
                        $relationConfig['foreignPivotKey'] => $parentId,
                        $relationConfig['relatedPivotKey'] => $item['id'] ?? $item
                    ];
                }, $data['attach'] ?? []);

                if (!empty($pivotData)) {
                    $this->create($relationConfig['table'], $pivotData, [
                        'transaction' => false
                    ]);
                }
                break;
        }
    }

    protected function generateUuid(): string
    {
        // Using MySQL's UUID() function
        $result = $this->db->query("SELECT UUID() as uuid");
        return $result[0]['uuid'];
    }
}
