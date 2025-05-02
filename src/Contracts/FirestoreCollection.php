<?php

namespace Frakt24\LaravelPHPFirestore\Contracts;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreDocument;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreQuery;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreSnapshot;
use Frakt24\LaravelPHPFirestore\Contracts\FirestoreBatch;

interface FirestoreCollection
{
    /**
     * Get the collection path
     */
    public function path(): string;

    /**
     * Add a document to the collection
     */
    public function add($data, ?string $id = null): FirestoreDocument;

    /**
     * Add a document with automatic timestamps
     */
    public function addWithTimestamp($data, ?string $id = null): FirestoreDocument;

    /**
     * Get a document by ID
     */
    public function document(string $id): FirestoreDocument;

    /**
     * Create a new query on the collection
     */
    public function query(): FirestoreQuery;

    /**
     * Get a list of documents in the collection
     */
    public function list(int $pageSize = 100): FirestoreSnapshot;

    /**
     * Get a batch writer for the collection
     */
    public function batch(): FirestoreBatch;

    /**
     * Start a transaction on the collection
     */
    public function transaction(callable $callback);

    /**
     * Get a subcollection
     */
    public function collection(string $path): self;

    /**
     * Listen for real-time updates
     */
    public function listen(callable $callback, array $options = []);
}
