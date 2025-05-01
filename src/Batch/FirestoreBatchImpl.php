<?php

namespace Frakt24\LaravelPHPFirestore\Batch;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreBatch;
use Frakt24\LaravelPHPFirestore\FirestoreService;
use Frakt24\LaravelPHPFirestore\Exceptions\Batch\BatchSizeExceededException;

class FirestoreBatchImpl implements FirestoreBatch
{
    private FirestoreService $service;
    private string $basePath;
    private array $operations = [];
    private const MAX_BATCH_SIZE = 500;

    public function __construct(FirestoreService $service, string $basePath)
    {
        $this->service = $service;
        $this->basePath = $basePath;
    }

    public function create(string $path, $data): FirestoreBatch
    {
        $this->checkSize();
        $this->operations[] = [
            'type' => 'create',
            'path' => $this->resolvePath($path),
            'data' => $data
        ];
        return $this;
    }

    public function set(string $path, $data, bool $merge = false): FirestoreBatch
    {
        $this->checkSize();
        $this->operations[] = [
            'type' => 'set',
            'path' => $this->resolvePath($path),
            'data' => $data,
            'merge' => $merge
        ];
        return $this;
    }

    public function update(string $path, array $data): FirestoreBatch
    {
        $this->checkSize();
        $this->operations[] = [
            'type' => 'update',
            'path' => $this->resolvePath($path),
            'data' => $data
        ];
        return $this;
    }

    public function delete(string $path): FirestoreBatch
    {
        $this->checkSize();
        $this->operations[] = [
            'type' => 'delete',
            'path' => $this->resolvePath($path)
        ];
        return $this;
    }

    public function commit(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $results = [];
        $currentBatch = [];
        $count = 0;

        foreach ($this->operations as $operation) {
            $currentBatch[] = $operation;
            $count++;

            if ($count === self::MAX_BATCH_SIZE) {
                $results = array_merge($results, $this->commitBatch($currentBatch));
                $currentBatch = [];
                $count = 0;
            }
        }

        if (!empty($currentBatch)) {
            $results = array_merge($results, $this->commitBatch($currentBatch));
        }

        $this->clear();
        return $results;
    }

    public function size(): int
    {
        return count($this->operations);
    }

    public function isEmpty(): bool
    {
        return empty($this->operations);
    }

    public function clear(): FirestoreBatch
    {
        $this->operations = [];
        return $this;
    }

    /**
     * Commit a single batch of operations
     */
    private function commitBatch(array $operations): array
    {
        return $this->service->commitBatch($operations);
    }

    /**
     * Check if adding another operation would exceed the batch size limit
     */
    private function checkSize(): void
    {
        if ($this->size() >= self::MAX_BATCH_SIZE) {
            throw new BatchSizeExceededException(
                $this->size(),
                self::MAX_BATCH_SIZE,
                "Batch size would exceed maximum of " . self::MAX_BATCH_SIZE
            );
        }
    }

    /**
     * Resolve a path relative to the base path
     */
    private function resolvePath(string $path): string
    {
        if (strpos($path, '/') === 0) {
            return ltrim($path, '/');
        }
        return $this->basePath . '/' . $path;
    }

    /**
     * Add multiple operations at once
     */
    public function addOperations(array $operations): FirestoreBatch
    {
        foreach ($operations as $operation) {
            $type = $operation['type'] ?? null;
            $path = $operation['path'] ?? null;
            $data = $operation['data'] ?? null;

            switch ($type) {
                case 'create':
                    $this->create($path, $data);
                    break;
                case 'set':
                    $merge = $operation['merge'] ?? false;
                    $this->set($path, $data, $merge);
                    break;
                case 'update':
                    $this->update($path, $data);
                    break;
                case 'delete':
                    $this->delete($path);
                    break;
                default:
                    throw new \InvalidArgumentException("Invalid operation type: $type");
            }
        }

        return $this;
    }
}
