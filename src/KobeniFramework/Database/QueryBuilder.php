<?php

namespace KobeniFramework\Database;

class QueryBuilder
{
    protected array $select = ['*'];
    protected array $where = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [];

    public function __construct(
        protected DB $db,
        protected string $table
    ) {}

    public function select(array $fields): self
    {
        $this->select = $fields;
        return $this;
    }

    public function where(array $conditions): self
    {
        if (empty($conditions)) {
            return $this;
        }

        foreach ($conditions as $key => $value) {
            if ($key === 'OR') {
                $this->addOrCondition($value);
            } else {
                $this->addBasicCondition($key, $value);
            }
        }
        return $this;
    }

    protected function addConditionGroup(string $type, array $conditions): void
    {
        $group = ['type' => $type, 'conditions' => []];

        foreach ($conditions as $condition) {
            foreach ($condition as $key => $value) {
                if ($key === 'OR' || $key === 'AND') {
                    $group['conditions'][] = $this->createConditionGroup($key, $value);
                } else {
                    $group['conditions'][] = $this->createCondition($key, $value);
                }
            }
        }

        $this->where[] = $group;
    }

    protected function addCondition(string $field, mixed $value): void
    {
        if (is_array($value)) {
            [$operator, $operand] = $value;
            $this->where[] = [
                'type' => 'basic',
                'field' => $field,
                'operator' => $operator,
                'value' => $operand
            ];
            $this->bindings[] = $operand;
        } else {
            $this->where[] = [
                'type' => 'basic',
                'field' => $field,
                'operator' => '=',
                'value' => $value
            ];
            $this->bindings[] = $value;
        }
    }

    protected function createConditionGroup(string $type, array $conditions): array
    {
        $group = ['type' => $type, 'conditions' => []];
        foreach ($conditions as $condition) {
            $group['conditions'][] = $this->createCondition(...$condition);
        }
        return $group;
    }

    protected function createCondition(string $field, mixed $value): array
    {
        if (is_array($value)) {
            [$operator, $operand] = $value;
            $this->bindings[] = $operand;
            return [
                'type' => 'basic',
                'field' => $field,
                'operator' => $operator,
                'value' => '?'
            ];
        }

        $this->bindings[] = $value;
        return [
            'type' => 'basic',
            'field' => $field,
            'operator' => '=',
            'value' => '?'
        ];
    }

    public function orderBy(string|array $field, ?string $direction = 'ASC'): self
    {
        if (is_array($field)) {
            foreach ($field as $f => $d) {
                $this->orderBy[] = [
                    'field' => $f,
                    'direction' => strtoupper($d)
                ];
            }
        } else {
            $this->orderBy[] = [
                'field' => $field,
                'direction' => strtoupper($direction)
            ];
        }
        return $this;
    }

    public function take(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function skip(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $query = $this->compileSelect();
        return $this->db->query($query, $this->bindings);
    }

    protected function addOrCondition(array $conditions): void
    {
        $orClauses = [];
        foreach ($conditions as $condition) {
            foreach ($condition as $field => $value) {
                $this->bindings[] = $value;
                $orClauses[] = "`$field` = ?";
            }
        }

        if (!empty($orClauses)) {
            $this->where[] = [
                'type' => 'raw',
                'sql' => '(' . implode(' OR ', $orClauses) . ')'
            ];
        }
    }

    protected function addBasicCondition(string $field, mixed $value): void
    {
        if (is_array($value)) {
            [$operator, $operand] = $value;
            $this->where[] = [
                'type' => 'basic',
                'field' => $field,
                'operator' => $operator,
                'value' => $operand
            ];
            $this->bindings[] = $operand;
        } else {
            $this->where[] = [
                'type' => 'basic',
                'field' => $field,
                'operator' => '=',
                'value' => $value
            ];
            $this->bindings[] = $value;
        }
    }

    protected function compileWhere(): string
    {
        if (empty($this->where)) {
            return '';
        }

        $conditions = array_map(function ($condition) {
            if ($condition['type'] === 'raw') {
                return $condition['sql'];
            }

            return sprintf(
                "`%s` %s ?",
                $condition['field'],
                $condition['operator']
            );
        }, $this->where);

        return implode(' AND ', $conditions);
    }

    protected function compileSelect(): string
    {
        $fields = array_map(function ($field) {
            return $field === '*' ? '*' : "`$field`";
        }, $this->select);

        $query = sprintf(
            "SELECT %s FROM `%s`",
            implode(', ', $fields),
            $this->table
        );

        if (!empty($this->where)) {
            $query .= " WHERE " . $this->compileWhere();
        }

        if (!empty($this->orderBy)) {
            $query .= " ORDER BY " . $this->compileOrderBy();
        }

        if ($this->limit !== null) {
            $query .= " LIMIT " . $this->limit;

            if ($this->offset !== null) {
                $query .= " OFFSET " . $this->offset;
            }
        }

        // Debug the final query
        // var_dump([
        //     'sql' => $query,
        //     'bindings' => $this->bindings,
        //     'where' => $this->where
        // ]);

        return $query;
    }

    protected function compileConditionGroup(array $group): string
    {
        $conditions = array_map(
            fn($condition) => $this->compileCondition($condition),
            $group['conditions']
        );

        $glue = " {$group['type']} ";
        return '(' . implode($glue, $conditions) . ')';
    }

    protected function compileCondition(array $condition): string
    {
        return sprintf(
            "`%s` %s %s",
            $condition['field'],
            $condition['operator'],
            $condition['value']
        );
    }

    protected function compileOrderBy(): string
    {
        return implode(', ', array_map(
            fn($order) => sprintf("`%s` %s", $order['field'], $order['direction']),
            $this->orderBy
        ));
    }

    public function update(array $data): bool
    {
        $sets = [];
        $values = [];

        foreach ($data as $field => $value) {
            $sets[] = "`$field` = ?";
            $values[] = $value;
        }

        $query = sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $this->table,
            implode(', ', $sets),
            $this->compileWhere()
        );

        $values = array_merge($values, $this->bindings);
        return $this->db->query($query, $values) !== false;
    }

    public function delete(): bool
    {
        $query = sprintf(
            "DELETE FROM `%s` WHERE %s",
            $this->table,
            $this->compileWhere()
        );

        return $this->db->query($query, $this->bindings) !== false;
    }
}
