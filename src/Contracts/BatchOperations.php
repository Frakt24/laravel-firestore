<?php

namespace Frakt24\LaravelPHPFirestore\Contracts;

interface BatchOperations
{
    /**
     * Add a write operation to the batch
     *
     * @param string $operation Type of operation (create, update, delete)
     * @param string $documentPath Path to the document
     * @param array $data Document data (for create/update)
     * @param array $options Additional options
     * @return self
     */
    public function add(string $operation, string $documentPath, array $data = [], array $options = []): self;

    /**
     * Commit the batch operations
     *
     * @return array Results of the commit
     */
    public function commit(): array;

    /**
     * Clear all operations from the batch
     *
     * @return self
     */
    public function clear(): self;

    /**
     * Get the current number of operations in the batch
     *
     * @return int
     */
    public function size(): int;
}
