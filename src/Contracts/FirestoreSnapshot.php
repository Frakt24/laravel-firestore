<?php

namespace Frakt24\LaravelPHPFirestore\Contracts;

interface FirestoreSnapshot
{
    /**
     * Get all documents in the snapshot
     */
    public function documents(): array;

    /**
     * Check if the snapshot is empty
     */
    public function isEmpty(): bool;

    /**
     * Get the size of the snapshot
     */
    public function size(): int;

    /**
     * Get the last document in the snapshot
     */
    public function lastDocument();

    /**
     * Get changes since the last snapshot
     */
    public function changes(): array;

    /**
     * Get metadata about the snapshot
     */
    public function metadata(): array;

    /**
     * Check if this is from cache
     */
    public function isFromCache(): bool;

    /**
     * Get the read time of the snapshot
     */
    public function readTime(): string;
}
