<?php

namespace Frakt24\LaravelPHPFirestore\Models\Concerns;

use RuntimeException;

trait HasNestedCollections
{
    /**
     * The parent document path segments.
     */
    protected array $parentPath = [];

    /**
     * Set the parent path for nested collections.
     */
    public function inPath(string|array $path): self
    {
        if (is_string($path)) {
            $path = array_filter(explode('/', $path));
        }

        // Validate path has even number of segments (collection/document pairs)
        if (count($path) % 2 !== 0) {
            throw new RuntimeException('Path must consist of collection/document pairs');
        }

        $this->parentPath = $path;
        return $this;
    }

    /**
     * Get the full collection path including parent documents.
     */
    protected function getCollectionPath(): string
    {
        $path = implode('/', $this->parentPath);
        return $path ? $path . '/' . $this->collection : $this->collection;
    }

    /**
     * Create a new instance in a nested path.
     */
    public static function in(string|array $path): static
    {
        return (new static)->inPath($path);
    }

    /**
     * Get the parent document if this is in a nested collection.
     */
    public function parent()
    {
        if (empty($this->parentPath)) {
            return null;
        }

        // Get the parent document path
        $parentPath = $this->parentPath;
        $documentId = array_pop($parentPath);
        $collection = array_pop($parentPath);

        // Find the appropriate model for the parent collection
        $modelClass = static::findModelForCollection($collection);
        if (!$modelClass) {
            return null;
        }

        return $modelClass::in($parentPath)->find($documentId);
    }

    /**
     * Find the appropriate model class for a collection name.
     * Override this in your base model to provide collection-to-model mapping.
     */
    protected static function findModelForCollection(string $collection): ?string
    {
        return null;
    }
}
