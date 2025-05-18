<?php

namespace Frakt24\LaravelPHPFirestore\Core;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreQuery;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreSnapshot;

class Query implements FirestoreQuery
{
    protected DatabaseResource $databaseResource;
    protected string $collectionPath;
    protected array $conditions = [];
    protected array $orderBy = [];
    protected ?int $limitValue = null;
    protected ?array $startAt = null;
    protected ?array $startAfter = null;
    protected ?array $endAt = null;
    protected ?array $endBefore = null;
    protected ?string $offset = null;

    public function __construct(DatabaseResource $databaseResource, string $collectionPath)
    {
        $this->databaseResource = $databaseResource;
        $this->collectionPath = $collectionPath;
    }

    /**
     * Add a where clause to the query
     */
    public function where(string $field, string $operator, $value): self
    {
        $this->conditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * Add an orderBy clause to the query
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orderBy[] = [
            'field' => $field,
            'direction' => $direction
        ];
        return $this;
    }

    /**
     * Limit the number of results
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Start query at a specific document
     */
    public function startAt($document): self
    {
        if ($document instanceof FirestoreDocument) {
            $this->startAt = ['values' => array_map([$this, 'encodeValue'], $document->data())];
        } else {
            $this->startAt = ['values' => array_map([$this, 'encodeValue'], (array) $document)];
        }
        return $this;
    }

    /**
     * Start query after a specific document
     */
    public function startAfter($document): self
    {
        if ($document instanceof FirestoreDocument) {
            $this->startAfter = ['values' => array_map([$this, 'encodeValue'], $document->data())];
        } else {
            $this->startAfter = ['values' => array_map([$this, 'encodeValue'], (array) $document)];
        }
        return $this;
    }

    /**
     * End query at a specific document
     */
    public function endAt($document): self
    {
        if ($document instanceof FirestoreDocument) {
            $this->endAt = ['values' => array_map([$this, 'encodeValue'], $document->data())];
        } else {
            $this->endAt = ['values' => array_map([$this, 'encodeValue'], (array) $document)];
        }
        return $this;
    }

    /**
     * End query before a specific document
     */
    public function endBefore($document): self
    {
        if ($document instanceof FirestoreDocument) {
            $this->endBefore = ['values' => array_map([$this, 'encodeValue'], $document->data())];
        } else {
            $this->endBefore = ['values' => array_map([$this, 'encodeValue'], (array) $document)];
        }
        return $this;
    }

    /**
     * Execute the query and get the results
     */
    public function get(): array
    {
        $structuredQuery = [
            'from' => [['collectionId' => basename($this->collectionPath)]]
        ];

        if (!empty($this->conditions)) {
            $structuredQuery['where'] = $this->buildWhereClause();
        }

        if (!empty($this->orderBy)) {
            $structuredQuery['orderBy'] = $this->buildOrderByClause();
        }

        if ($this->limitValue !== null) {
            $structuredQuery['limit'] = $this->limitValue;
        }

        if ($this->startAt !== null) {
            $structuredQuery['startAt'] = $this->startAt;
        }

        if ($this->startAfter !== null) {
            $structuredQuery['startAfter'] = $this->startAfter;
        }

        if ($this->endAt !== null) {
            $structuredQuery['endAt'] = $this->endAt;
        }

        if ($this->endBefore !== null) {
            $structuredQuery['endBefore'] = $this->endBefore;
        }

        if ($this->offset !== null) {
            $structuredQuery['offset'] = $this->offset;
        }

        return $this->databaseResource->runQuery($structuredQuery);
    }

    /**
     * Execute the query and get the first result
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Get the query as a snapshot
     */
    public function snapshot(): FirestoreSnapshot
    {
        $results = $this->get();
        return new Snapshot($results, $this->databaseResource);
    }

    protected function buildWhereClause(): array
    {
        if (count($this->conditions) === 1) {
            return $this->buildSingleCondition($this->conditions[0]);
        }

        $compositeFilter = [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => []
            ]
        ];

        foreach ($this->conditions as $condition) {
            $compositeFilter['compositeFilter']['filters'][] = $this->buildSingleCondition($condition);
        }

        return $compositeFilter;
    }

    protected function buildSingleCondition(array $condition): array
    {
        return [
            'fieldFilter' => [
                'field' => ['fieldPath' => $condition['field']],
                'op' => $this->mapOperator($condition['operator']),
                'value' => $this->encodeValue($condition['value'])
            ]
        ];
    }

    protected function buildOrderByClause(): array
    {
        $orderBy = [];
        foreach ($this->orderBy as $order) {
            $orderBy[] = [
                'field' => ['fieldPath' => $order['field']],
                'direction' => strtoupper($order['direction'])
            ];
        }
        return $orderBy;
    }

    protected function mapOperator(string $operator): string
    {
        $map = [
            '=' => 'EQUAL',
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

        return $map[$operator] ?? 'EQUAL';
    }

    protected function encodeValue($value): array
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

        if (is_array($value)) {
            return ['arrayValue' => ['values' => array_map([$this, 'encodeValue'], $value)]];
        }

        throw new \InvalidArgumentException('Unsupported value type');
    }
}
