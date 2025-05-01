<?php

namespace Frakt24\LaravelPHPFirestore\Query;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreQuery;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreSnapshot;
use Frakt24\LaravelPHPFirestore\FirestoreService;
use Frakt24\LaravelPHPFirestore\Exceptions\Query\QueryException;

class FirestoreQueryImpl implements FirestoreQuery
{
    private FirestoreService $service;
    private string $collection;
    private array $conditions = [];
    private array $orderBy = [];
    private ?int $limitValue = null;
    private ?array $startAt = null;
    private ?array $startAfter = null;
    private ?array $endAt = null;
    private ?array $endBefore = null;

    public function __construct(FirestoreService $service, string $collection)
    {
        $this->service = $service;
        $this->collection = $collection;
    }

    public function where(string $field, string $operator, $value): FirestoreQuery
    {
        $this->validateOperator($operator);
        $this->conditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): FirestoreQuery
    {
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new QueryException("Invalid order direction: $direction");
        }

        $this->orderBy[] = [
            'field' => $field,
            'direction' => $direction
        ];
        return $this;
    }

    public function limit(int $limit): FirestoreQuery
    {
        if ($limit < 1) {
            throw new QueryException("Limit must be greater than 0");
        }
        $this->limitValue = $limit;
        return $this;
    }

    public function startAt($document): FirestoreQuery
    {
        $this->startAt = $this->getDocumentValues($document);
        return $this;
    }

    public function startAfter($document): FirestoreQuery
    {
        $this->startAfter = $this->getDocumentValues($document);
        return $this;
    }

    public function endAt($document): FirestoreQuery
    {
        $this->endAt = $this->getDocumentValues($document);
        return $this;
    }

    public function endBefore($document): FirestoreQuery
    {
        $this->endBefore = $this->getDocumentValues($document);
        return $this;
    }

    public function get(): array
    {
        return $this->service->runQuery($this->collection, [
            'conditions' => $this->conditions,
            'orderBy' => $this->orderBy,
            'limit' => $this->limitValue,
            'startAt' => $this->startAt,
            'startAfter' => $this->startAfter,
            'endAt' => $this->endAt,
            'endBefore' => $this->endBefore
        ]);
    }

    public function first()
    {
        $results = $this->limit(1)->get();
        return !empty($results) ? $results[0] : null;
    }

    public function snapshot(): FirestoreSnapshot
    {
        return new FirestoreSnapshotImpl($this->get());
    }

    /**
     * Get values from a document for cursor operations
     */
    private function getDocumentValues($document): array
    {
        if (is_array($document)) {
            return $document;
        }

        if (method_exists($document, 'toArray')) {
            return $document->toArray();
        }

        throw new QueryException("Invalid document format for cursor operation");
    }

    /**
     * Validate query operator
     */
    private function validateOperator(string $operator): void
    {
        $validOperators = ['<', '<=', '==', '>=', '>', '!=', 'array-contains', 'in', 'array-contains-any', 'not-in'];
        
        if (!in_array($operator, $validOperators)) {
            throw new QueryException("Invalid query operator: $operator");
        }
    }

    /**
     * Add a compound where clause
     */
    public function whereCompound(array $conditions): FirestoreQuery
    {
        foreach ($conditions as $condition) {
            if (!isset($condition['field'], $condition['operator'], $condition['value'])) {
                throw new QueryException("Invalid compound where condition");
            }
            $this->where($condition['field'], $condition['operator'], $condition['value']);
        }
        return $this;
    }

    /**
     * Add an array-contains clause
     */
    public function whereArrayContains(string $field, $value): FirestoreQuery
    {
        return $this->where($field, 'array-contains', $value);
    }

    /**
     * Add an in clause
     */
    public function whereIn(string $field, array $values): FirestoreQuery
    {
        return $this->where($field, 'in', $values);
    }

    /**
     * Get the total count of documents matching the query
     */
    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Check if any documents match the query
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
