<?php

namespace Frakt24\LaravelPHPFirestore\Collections;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreBatch;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreCollection;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreQuery;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreSnapshot;
use Frakt24\LaravelPHPFirestore\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\FirestoreService;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreTimestamp;
use Frakt24\LaravelPHPFirestore\Exceptions\Client\InvalidPathProvided;

class FirestoreCollectionImpl implements FirestoreCollection
{
    private FirestoreService $service;
    private string $path;
    private bool $timestamps;
    private ?string $parent;

    public function __construct(FirestoreService $service, string $path, bool $timestamps = true, ?string $parent = null)
    {
        $this->service = $service;
        $this->path = $this->normalizePath($path);
        $this->timestamps = $timestamps;
        $this->parent = $parent;
    }

    public function path(): string
    {
        return $this->parent ? "{$this->parent}/{$this->path}" : $this->path;
    }

    public function add($data, ?string $id = null): FirestoreDocument
    {
        if ($this->timestamps) {
            $data = $this->addTimestamps($data);
        }

        return $this->service->addDocument($this->path(), $data, $id);
    }

    public function addWithTimestamp($data, ?string $id = null): FirestoreDocument
    {
        return $this->add($data, $id);
    }

    public function document(string $id): FirestoreDocument
    {
        return $this->service->getDocument("{$this->path()}/$id");
    }

    public function query(): FirestoreQuery
    {
        return new FirestoreQueryImpl($this->service, $this->path());
    }

    public function list(int $pageSize = 100): FirestoreSnapshot
    {
        return $this->query()->limit($pageSize)->snapshot();
    }

    public function batch(): FirestoreBatch
    {
        return new FirestoreBatchImpl($this->service, $this->path());
    }

    public function transaction(callable $callback)
    {
        return $this->service->runTransaction($callback);
    }

    public function collection(string $path): FirestoreCollection
    {
        return new self($this->service, $path, $this->timestamps, $this->path());
    }

    public function listen(callable $callback, array $options = [])
    {
        return $this->service->listen($this->path(), $callback, $options);
    }

    /**
     * Delete a document from the collection
     */
    public function delete(string $id): bool
    {
        return $this->service->deleteDocument("{$this->path()}/$id");
    }

    /**
     * Update a document in the collection
     */
    public function update(string $id, array $data): FirestoreDocument
    {
        if ($this->timestamps) {
            $data['updatedAt'] = new FirestoreTimestamp();
        }

        return $this->service->updateDocument("{$this->path()}/$id", $data);
    }

    /**
     * Check if a document exists in the collection
     */
    public function exists(string $id): bool
    {
        try {
            $this->document($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get multiple documents by their IDs
     */
    public function getMultiple(array $ids): array
    {
        $documents = [];
        foreach ($ids as $id) {
            try {
                $documents[$id] = $this->document($id);
            } catch (\Exception $e) {
                continue;
            }
        }
        return $documents;
    }

    /**
     * Add timestamps to data
     */
    private function addTimestamps($data): array
    {
        if (is_array($data)) {
            $timestamp = new FirestoreTimestamp();
            $data['createdAt'] = $timestamp;
            $data['updatedAt'] = $timestamp;
        } elseif ($data instanceof FirestoreDocument) {
            $data->setTimestamp('createdAt', new FirestoreTimestamp());
            $data->setTimestamp('updatedAt', new FirestoreTimestamp());
        }
        return $data;
    }

    /**
     * Normalize the collection path
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        
        if (empty($path)) {
            throw new InvalidPathProvided('Collection path cannot be empty');
        }

        // Validate path segments
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if (empty($segment)) {
                throw new InvalidPathProvided('Collection path segments cannot be empty');
            }
        }

        return $path;
    }

    /**
     * Get parent document if this is a subcollection
     */
    public function parent(): ?FirestoreDocument
    {
        if (!$this->parent) {
            return null;
        }

        return $this->service->getDocument($this->parent);
    }

    /**
     * Get the full path including parent
     */
    public function fullPath(): string
    {
        return $this->path();
    }
}
