<?php

namespace Frakt24\LaravelPHPFirestore\Query;

class FirestoreStructuredQuery
{
    private $select = [];
    private $from = [];
    private $where = [];
    private $orderBy = [];
    private $startAt;
    private $endAt;
    private $offset;
    private $limit;

    /**
     * Select specific fields
     *
     * @param array $fields Field paths to return
     * @return self
     */
    public function select(array $fields): self
    {
        $this->select = ['fields' => array_map(function($field) {
            return ['fieldPath' => $field];
        }, $fields)];
        return $this;
    }

    /**
     * Specify the collection to query
     *
     * @param string $collectionId Collection ID
     * @param bool $allDescendants Whether to include documents from subcollections
     * @return self
     */
    public function from(string $collectionId, bool $allDescendants = false): self
    {
        $this->from[] = [
            'collectionId' => $collectionId,
            'allDescendants' => $allDescendants
        ];
        return $this;
    }

    /**
     * Add a where clause
     *
     * @param string $field Field path
     * @param string $op Operator (==, <, <=, >, >=, !=, array-contains, in, array-contains-any, not-in)
     * @param mixed $value Value to compare against
     * @return self
     */
    public function where(string $field, string $op, $value): self
    {
        $operators = [
            '==' => 'EQUAL',
            '<' => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>' => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
            '!=' => 'NOT_EQUAL',
            'array-contains' => 'ARRAY_CONTAINS',
            'in' => 'IN',
            'array-contains-any' => 'ARRAY_CONTAINS_ANY',
            'not-in' => 'NOT_IN'
        ];

        $this->where[] = [
            'fieldFilter' => [
                'field' => ['fieldPath' => $field],
                'op' => $operators[$op],
                'value' => $this->encodeValue($value)
            ]
        ];
        return $this;
    }

    /**
     * Add a compound where clause
     *
     * @param array $conditions Array of conditions, each with [field, op, value]
     * @param string $operator Compound operator ('AND' or 'OR')
     * @return self
     */
    public function whereCompound(array $conditions, string $operator = 'AND'): self
    {
        $filters = [];
        foreach ($conditions as $condition) {
            [$field, $op, $value] = $condition;
            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $field],
                    'op' => $this->getOperator($op),
                    'value' => $this->encodeValue($value)
                ]
            ];
        }

        $this->where[] = [
            'compositeFilter' => [
                'op' => strtoupper($operator),
                'filters' => $filters
            ]
        ];
        return $this;
    }

    /**
     * Add a filter for documents where a field exists
     *
     * @param string $field Field path
     * @return self
     */
    public function whereExists(string $field): self
    {
        $this->where[] = [
            'unaryFilter' => [
                'field' => ['fieldPath' => $field],
                'op' => 'IS_NOT_NULL'
            ]
        ];
        return $this;
    }

    /**
     * Add a filter for documents where a field does not exist
     *
     * @param string $field Field path
     * @return self
     */
    public function whereNotExists(string $field): self
    {
        $this->where[] = [
            'unaryFilter' => [
                'field' => ['fieldPath' => $field],
                'op' => 'IS_NULL'
            ]
        ];
        return $this;
    }

    /**
     * Add a filter for documents where a field matches a pattern
     *
     * @param string $field Field path
     * @param string $pattern Pattern to match (supports * and **)
     * @return self
     */
    public function whereMatches(string $field, string $pattern): self
    {
        return $this->where($field, '==', [
            'stringValue' => $pattern
        ]);
    }

    /**
     * Add an orderBy clause
     *
     * @param string $field Field to order by
     * @param string $direction Direction (asc or desc)
     * @return self
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orderBy[] = [
            'field' => ['fieldPath' => $field],
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * Set the starting point
     *
     * @param array $values Values that define the start point
     * @param bool $before Whether to start before the values
     * @return self
     */
    public function startAt(array $values, bool $before = false): self
    {
        $this->startAt = [
            'values' => array_map([$this, 'encodeValue'], $values),
            'before' => $before
        ];
        return $this;
    }

    /**
     * Set the ending point
     *
     * @param array $values Values that define the end point
     * @param bool $before Whether to end before the values
     * @return self
     */
    public function endAt(array $values, bool $before = true): self
    {
        $this->endAt = [
            'values' => array_map([$this, 'encodeValue'], $values),
            'before' => $before
        ];
        return $this;
    }

    /**
     * Set the number of results to skip
     *
     * @param int $offset Number of results to skip
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set the maximum number of results to return
     *
     * @param int $limit Maximum number of results
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Build the structured query
     *
     * @return array
     */
    public function build(): array
    {
        $query = [];

        if (!empty($this->select)) {
            $query['select'] = $this->select;
        }

        if (!empty($this->from)) {
            $query['from'] = $this->from;
        }

        if (!empty($this->where)) {
            $query['where'] = ['compositeFilter' => [
                'op' => 'AND',
                'filters' => $this->where
            ]];
        }

        if (!empty($this->orderBy)) {
            $query['orderBy'] = $this->orderBy;
        }

        if (isset($this->startAt)) {
            $query['startAt'] = $this->startAt;
        }

        if (isset($this->endAt)) {
            $query['endAt'] = $this->endAt;
        }

        if (isset($this->offset)) {
            $query['offset'] = $this->offset;
        }

        if (isset($this->limit)) {
            $query['limit'] = $this->limit;
        }

        return $query;
    }

    /**
     * Encode a value for Firestore
     *
     * @param mixed $value
     * @return array
     */
    private function encodeValue($value): array
    {
        if (is_null($value)) {
            return ['nullValue' => null];
        }
        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }
        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }
        if (is_float($value)) {
            return ['doubleValue' => $value];
        }
        if (is_string($value)) {
            return ['stringValue' => $value];
        }
        if ($value instanceof \DateTime) {
            return ['timestampValue' => $value->format('c')];
        }
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                return ['arrayValue' => ['values' => array_map([$this, 'encodeValue'], $value)]];
            }
            return ['mapValue' => ['fields' => array_map([$this, 'encodeValue'], $value)]];
        }
        
        throw new \InvalidArgumentException('Unsupported value type');
    }

    private function getOperator(string $op): string
    {
        $operators = [
            '==' => 'EQUAL',
            '<' => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>' => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
            '!=' => 'NOT_EQUAL',
            'array-contains' => 'ARRAY_CONTAINS',
            'in' => 'IN',
            'array-contains-any' => 'ARRAY_CONTAINS_ANY',
            'not-in' => 'NOT_IN'
        ];

        if (!isset($operators[$op])) {
            throw new \InvalidArgumentException("Unsupported operator: {$op}");
        }

        return $operators[$op];
    }
}
