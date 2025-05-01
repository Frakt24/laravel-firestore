<?php

namespace Frakt24\LaravelPHPFirestore\Contracts;

interface FirestoreQuery
{
    /**
     * Add a where clause to the query
     */
    public function where(string $field, string $operator, $value): self;

    /**
     * Add an orderBy clause to the query
     */
    public function orderBy(string $field, string $direction = 'asc'): self;

    /**
     * Limit the number of results
     */
    public function limit(int $limit): self;

    /**
     * Start query at a specific document
     */
    public function startAt($document): self;

    /**
     * Start query after a specific document
     */
    public function startAfter($document): self;

    /**
     * End query at a specific document
     */
    public function endAt($document): self;

    /**
     * End query before a specific document
     */
    public function endBefore($document): self;

    /**
     * Execute the query and get the results
     */
    public function get(): array;

    /**
     * Execute the query and get the first result
     */
    public function first();

    /**
     * Get the query as a snapshot
     */
    public function snapshot(): FirestoreSnapshot;
}
