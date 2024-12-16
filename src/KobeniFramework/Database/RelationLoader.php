<?php

namespace KobeniFramework\Database;

class RelationLoader
{
    protected array $bindings = [];

    public function __construct(
        protected DB $db,
        protected array $relation,
        protected ?string $relationName = null,
        protected Kobeni $kobeni
    ) {}

    public function load(array $parent, array $options = []): mixed
    {
        $this->bindings = [];

        $result = match ($this->relation['type']) {
            'belongsTo' => $this->loadBelongsTo($parent, $options),
            'hasOne' => $this->loadHasOne($parent, $options),
            'hasMany' => $this->loadHasMany($parent, $options),
            'belongsToMany' => $this->loadBelongsToMany($parent, $options),
            default => null
        };

        if ($result !== null && isset($options['include'])) {
            if (is_array($result)) {
                foreach ($result as &$item) {
                    $item = $this->kobeni->loadIncludes(
                        $this->relation['model'],
                        $item,
                        $options['include']
                    );
                }
            } else {
                $result = $this->kobeni->loadIncludes(
                    $this->relation['model'],
                    $result,
                    $options['include']
                );
            }
        }

        return $result;
    }

    protected function loadBelongsTo(array $child, array $options): ?array
    {
        $foreignKey = $this->relation['foreignKey'];
        if (!isset($child[$foreignKey])) {
            return null;
        }

        $query = new QueryBuilder($this->db, $this->relation['model']);
        $query->where(['id' => $child[$foreignKey]]);

        $this->applyQueryOptions($query, $options);

        $results = $query->get();
        return $results[0] ?? null;
    }

    protected function loadHasOne(array $parent, array $options): ?array
    {
        $query = new QueryBuilder($this->db, $this->relation['model']);
        $query->where([$this->relation['foreignKey'] => $parent['id']]);

        $this->applyQueryOptions($query, $options);

        $results = $query->take(1)->get();
        return $results[0] ?? null;
    }

    protected function loadHasMany(array $parent, array $options): array
    {
        $query = new QueryBuilder($this->db, $this->relation['model']);
        $query->where([$this->relation['foreignKey'] => $parent['id']]);

        $this->applyQueryOptions($query, $options);

        return $query->get();
    }

    protected function loadBelongsToMany(array $parent, array $options): array
    {
        $pivotTable = $this->relation['table'];
        $relatedModel = $this->relation['model'];
        $this->bindings = [$parent['id']]; 

        $select = isset($options['select'])
            ? implode(', ', array_map(fn($field) => "r.`$field`", $options['select']))
            : 'r.*';

        $baseQuery = "SELECT $select 
                     FROM `$relatedModel` r
                     JOIN `$pivotTable` p ON p.{$this->relation['relatedPivotKey']} = r.id
                     WHERE p.{$this->relation['foreignPivotKey']} = ?";

        if (isset($options['where'])) {
            $whereClause = $this->buildWhereClause($options['where']);
            $baseQuery .= " AND " . $whereClause;
        }

        if (isset($options['orderBy'])) {
            if (is_array($options['orderBy'])) {
                $orderClauses = [];
                foreach ($options['orderBy'] as $field => $direction) {
                    $orderClauses[] = "r.`$field` $direction";
                }
                $baseQuery .= " ORDER BY " . implode(', ', $orderClauses);
            } else {
                $baseQuery .= " ORDER BY r.`{$options['orderBy'][0]}` {$options['orderBy'][1]}";
            }
        }

        if (isset($options['take'])) {
            $baseQuery .= " LIMIT " . (int)$options['take'];

            if (isset($options['skip'])) {
                $baseQuery .= " OFFSET " . (int)$options['skip'];
            }
        }

        return $this->db->query($baseQuery, $this->bindings);
    }

    protected function applyQueryOptions(QueryBuilder $query, array $options): void
    {
        if (isset($options['select'])) {
            $query->select($options['select']);
        }

        if (isset($options['where'])) {
            $query->where($options['where']);
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

    protected function buildWhereClause(array $conditions): string
    {
        $clauses = [];

        foreach ($conditions as $field => $value) {
            if ($field === 'OR' || $field === 'AND') {
                $subClauses = [];
                foreach ($value as $condition) {
                    $subClauses[] = $this->buildWhereCondition($condition);
                }
                $clauses[] = '(' . implode(" $field ", $subClauses) . ')';
            } else {
                $clauses[] = $this->buildWhereCondition([$field => $value]);
            }
        }

        return implode(' AND ', $clauses);
    }

    protected function buildWhereCondition(array $condition): string
    {
        foreach ($condition as $field => $value) {
            if (is_array($value)) {
                [$operator, $operand] = $value;
                $this->bindings[] = $operand;
                return "r.`$field` $operator ?";
            } else {
                $this->bindings[] = $value;
                return "r.`$field` = ?";
            }
        }
        return '';
    }
}
