<?php

namespace Frakt24\LaravelPHPFirestore\Traits;

use Frakt24\LaravelPHPFirestore\Fields\FirestoreArray;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreObject;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreTimestamp;

trait HasFirestoreAttributes
{
    protected array $attributes = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected string $createdAtField = 'createdAt';
    protected string $updatedAtField = 'updatedAt';

    /**
     * Get an attribute with automatic casting
     */
    public function getAttribute(string $key)
    {
        if (!isset($this->attributes[$key])) {
            return null;
        }

        $value = $this->attributes[$key];

        if (isset($this->casts[$key])) {
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    /**
     * Set an attribute with automatic casting
     */
    public function setAttribute(string $key, $value): void
    {
        if (isset($this->casts[$key])) {
            $value = $this->castAttributeForStorage($key, $value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Cast an attribute from Firestore format
     */
    protected function castAttribute(string $key, $value)
    {
        $type = $this->casts[$key];

        switch ($type) {
            case 'array':
                return $value instanceof FirestoreArray ? $value->toArray() : (array) $value;
            case 'object':
                return $value instanceof FirestoreObject ? $value->toArray() : (object) $value;
            case 'timestamp':
                return $value instanceof FirestoreTimestamp ? $value->toDateTime() : $value;
            case 'datetime':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            default:
                return $value;
        }
    }

    /**
     * Cast an attribute for Firestore storage
     */
    protected function castAttributeForStorage(string $key, $value)
    {
        $type = $this->casts[$key];

        switch ($type) {
            case 'array':
                return new FirestoreArray($value);
            case 'object':
                return new FirestoreObject($value);
            case 'timestamp':
                return $value instanceof FirestoreTimestamp ? $value : new FirestoreTimestamp($value);
            case 'datetime':
                return new FirestoreTimestamp($value);
            default:
                return $value;
        }
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set multiple attributes at once
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Add timestamps to the attributes
     */
    protected function addTimestamps(): void
    {
        if (!$this->timestamps) {
            return;
        }

        $now = new FirestoreTimestamp();

        if (!isset($this->attributes[$this->createdAtField])) {
            $this->attributes[$this->createdAtField] = $now;
        }

        $this->attributes[$this->updatedAtField] = $now;
    }
}
