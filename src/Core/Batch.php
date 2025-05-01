<?php

namespace Frakt24\LaravelPHPFirestore\Core;

use Frakt24\LaravelPHPFirestore\Contracts\BatchOperations;
use Frakt24\LaravelPHPFirestore\FirestoreClient;
use RuntimeException;

class Batch implements BatchOperations
{
    private FirestoreClient $client;
    private array $operations = [];
    private int $maxBatchSize = 500; // Firestore limit

    public function __construct(FirestoreClient $client)
    {
        $this->client = $client;
    }

    /**
     * Add a write operation to the batch
     *
     * @param string $operation Type of operation (create, update, delete)
     * @param string $documentPath Path to the document
     * @param array $data Document data (for create/update)
     * @param array $options Additional options
     * @return self
     * @throws RuntimeException If batch size would exceed limit
     */
    public function add(string $operation, string $documentPath, array $data = [], array $options = []): self
    {
        if (count($this->operations) >= $this->maxBatchSize) {
            throw new RuntimeException("Batch size cannot exceed {$this->maxBatchSize} operations");
        }

        $this->operations[] = [
            'type' => $operation,
            'path' => $documentPath,
            'data' => $data,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Commit the batch operations
     *
     * @return array Results of the commit
     */
    public function commit(): array
    {
        if (empty($this->operations)) {
            return ['writeResults' => []];
        }

        $writes = [];
        foreach ($this->operations as $op) {
            $write = [];
            switch ($op['type']) {
                case 'create':
                    $write['document'] = $op['data'];
                    $write['currentDocument'] = ['exists' => false];
                    break;
                case 'update':
                    $write['updateMask'] = ['fieldPaths' => array_keys($op['data'])];
                    $write['update'] = $op['data'];
                    if (!empty($op['options']['precondition'])) {
                        $write['currentDocument'] = $op['options']['precondition'];
                    }
                    break;
                case 'delete':
                    $write['delete'] = $op['path'];
                    break;
            }
            $writes[] = $write;
        }

        return $this->client->request('POST', ':batchWrite', [
            'json' => ['writes' => $writes]
        ]);
    }

    /**
     * Clear all operations from the batch
     *
     * @return self
     */
    public function clear(): self
    {
        $this->operations = [];
        return $this;
    }

    /**
     * Get the current number of operations in the batch
     *
     * @return int
     */
    public function size(): int
    {
        return count($this->operations);
    }
}
