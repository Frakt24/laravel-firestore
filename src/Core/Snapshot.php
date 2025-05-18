<?php

namespace Frakt24\LaravelPHPFirestore\Core;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreSnapshot;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDocument;

class Snapshot implements FirestoreSnapshot
{
    protected array $documents;
    protected DatabaseResource $databaseResource;
    protected array $metadata;
    protected array $changes;
    protected string $readTime;
    protected bool $fromCache;

    public function __construct(array $results, DatabaseResource $databaseResource)
    {
        $this->databaseResource = $databaseResource;
        $this->documents = array_map(function ($result) {
            return new Document(
                $result['name'],
                $result['name'],
                $result,
                $this->databaseResource
            );
        }, $results['documents'] ?? []);
        
        $this->metadata = $results['metadata'] ?? [];
        $this->changes = $results['changes'] ?? [];
        $this->readTime = $results['readTime'] ?? date('c');
        $this->fromCache = $results['fromCache'] ?? false;
    }

    /**
     * Get all documents in the snapshot
     */
    public function documents(): array
    {
        return $this->documents;
    }

    /**
     * Check if the snapshot is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->documents);
    }

    /**
     * Get the size of the snapshot
     */
    public function size(): int
    {
        return count($this->documents);
    }

    /**
     * Get the last document in the snapshot
     */
    public function lastDocument()
    {
        return !empty($this->documents) ? end($this->documents) : null;
    }

    /**
     * Get changes since the last snapshot
     */
    public function changes(): array
    {
        return $this->changes;
    }

    /**
     * Get metadata about the snapshot
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if this is from cache
     */
    public function isFromCache(): bool
    {
        return $this->fromCache;
    }

    /**
     * Get the read time of the snapshot
     */
    public function readTime(): string
    {
        return $this->readTime;
    }
}
