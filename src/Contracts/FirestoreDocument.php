<?php

namespace Frakt24\LaravelPHPFirestore\Contracts;

interface FirestoreDocument
{
    /**
     * Get the document ID
     */
    public function getId(): string;

    /**
     * Get the document path
     */
    public function path(): string;

    /**
     * Get the document data
     */
    public function data(): array;

    /**
     * Update the document
     */
    public function update(array $data): bool;

    /**
     * Delete the document
     */
    public function delete(): bool;

    /**
     * Get a subcollection
     */
    public function collection(string $path): FirestoreCollection;

    /**
     * Get the document's parent collection
     */
    public function parent(): FirestoreCollection;

    /**
     * Check if the document exists
     */
    public function exists(): bool;

    /**
     * Get the document's create time
     */
    public function createTime(): ?string;

    /**
     * Get the document's update time
     */
    public function updateTime(): ?string;
}
