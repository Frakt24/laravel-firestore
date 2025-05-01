<?php

namespace Frakt24\LaravelPHPFirestore\Contracts;

use Frakt24\LaravelPHPFirestore\FirestoreDocument;

interface FirestoreBatch
{
    /**
     * Add a create operation to the batch
     */
    public function create(string $path, $data): self;

    /**
     * Add a set operation to the batch
     */
    public function set(string $path, $data, bool $merge = false): self;

    /**
     * Add an update operation to the batch
     */
    public function update(string $path, array $data): self;

    /**
     * Add a delete operation to the batch
     */
    public function delete(string $path): self;

    /**
     * Commit the batch operations
     */
    public function commit(): array;

    /**
     * Get the current size of the batch
     */
    public function size(): int;

    /**
     * Check if the batch is empty
     */
    public function isEmpty(): bool;

    /**
     * Clear all operations from the batch
     */
    public function clear(): self;
}
