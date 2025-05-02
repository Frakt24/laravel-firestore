<?php

namespace Frakt24\LaravelPHPFirestore\Core;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreBatch;
use Frakt24\LaravelPHPFirestore\Core\Client;
use RuntimeException;

class Batch implements FirestoreBatch
{
    private Client $client;
    private array $operations = [];
    private int $maxBatchSize = 500; // Firestore limit

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Add a generic operation to the batch (for backward compatibility)
     *
     * @param string $operation Type of operation (create, update, delete)
     * @param string $path Path to the document
     * @param array $data Document data (for create/update)
     * @return self
     */
    public function add(string $operation, string $path, array $data = []): self
    {
        switch ($operation) {
            case 'create':
                return $this->create($path, $data);
            case 'update':
                return $this->update($path, $data);
            case 'delete':
                return $this->delete($path);
            case 'set':
                return $this->set($path, $data);
            default:
                throw new RuntimeException("Invalid operation type: {$operation}");
        }
    }

    /**
     * Add a create operation to the batch
     */
    public function create(string $path, $data): self
    {
        $this->checkBatchSize();
        $this->operations[] = [
            'type' => 'create',
            'path' => $path,
            'data' => $data
        ];
        return $this;
    }

    /**
     * Add a set operation to the batch
     */
    public function set(string $path, $data, bool $merge = false): self
    {
        $this->checkBatchSize();
        $this->operations[] = [
            'type' => 'set',
            'path' => $path,
            'data' => $data,
            'merge' => $merge
        ];
        return $this;
    }

    /**
     * Add an update operation to the batch
     */
    public function update(string $path, array $data): self
    {
        $this->checkBatchSize();
        $this->operations[] = [
            'type' => 'update',
            'path' => $path,
            'data' => $data
        ];
        return $this;
    }

    /**
     * Add a delete operation to the batch
     */
    public function delete(string $path): self
    {
        $this->checkBatchSize();
        $this->operations[] = [
            'type' => 'delete',
            'path' => $path
        ];
        return $this;
    }

    /**
     * Commit all operations in the batch
     */
    public function commit(): array
    {
        if (empty($this->operations)) {
            return [];
        }

        $writes = [];
        foreach ($this->operations as $operation) {
            $write = [];
            switch ($operation['type']) {
                case 'create':
                    $write['create'] = [
                        'document' => $operation['path'],
                        'fields' => $operation['data']
                    ];
                    break;
                case 'set':
                    $write['update'] = [
                        'document' => $operation['path'],
                        'fields' => $operation['data']
                    ];
                    if ($operation['merge']) {
                        $write['updateMask'] = ['fieldPaths' => array_keys($operation['data'])];
                    }
                    break;
                case 'update':
                    $write['update'] = [
                        'document' => $operation['path'],
                        'fields' => $operation['data']
                    ];
                    $write['updateMask'] = ['fieldPaths' => array_keys($operation['data'])];
                    break;
                case 'delete':
                    $write['delete'] = $operation['path'];
                    break;
            }
            $writes[] = $write;
        }

        $response = $this->client->request('POST', ':batchWrite', [
            'json' => ['writes' => $writes]
        ]);

        $this->operations = [];
        return $response['writeResults'] ?? [];
    }

    /**
     * Get the current size of the batch
     */
    public function size(): int
    {
        return count($this->operations);
    }

    /**
     * Check if the batch is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->operations);
    }

    /**
     * Clear all operations from the batch
     */
    public function clear(): self
    {
        $this->operations = [];
        return $this;
    }

    private function checkBatchSize(): void
    {
        if (count($this->operations) >= $this->maxBatchSize) {
            throw new RuntimeException("Batch operation limit of {$this->maxBatchSize} has been reached");
        }
    }
}
