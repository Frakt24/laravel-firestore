<?php

namespace Frakt24\LaravelPHPFirestore\Models;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreCollection;
use Frakt24\LaravelPHPFirestore\Core\Client;
use Frakt24\LaravelPHPFirestore\Core\Service;
use Frakt24\LaravelPHPFirestore\Traits\HasFirestoreAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Arr;
use Illuminate\Support\Collection;
use JsonSerializable;
use Carbon\Carbon;

abstract class FirestoreModel implements Arrayable, JsonSerializable
{
    use HasFirestoreAttributes;

    protected ?string $id = null;
    protected string $collection;
    protected array $guarded = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = ['createdAt', 'updatedAt', 'deletedAt'];
    protected bool $timestamps = true;
    protected string $dateFormat = 'Y-m-d H:i:s';
    protected bool $exists = false;
    protected static bool $globalTimestamps = true;
    protected static bool $globalSoftDeletes = true;
    protected static string $globalDateFormat = 'Y-m-d H:i:s';
    protected static array $globalDateColumns = [
        'created_at' => 'createdAt',
        'updated_at' => 'updatedAt',
        'deleted_at' => 'deletedAt',
    ];

    protected ?Client $client = null;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get the model's ID
     */
    public function getId(): ?string
    {
        return $this->getAttribute('id') ?? $this->id;
    }

    /**
     * Set the model's ID
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
        $this->setAttribute('id', $id);
    }

    /**
     * Set whether the model exists in Firestore
     */
    public function setExists(bool $exists): void
    {
        $this->exists = $exists;
    }

    /**
     * Get whether the model exists in Firestore
     */
    public function getExists(): bool
    {
        return $this->exists;
    }

    /**
     * Set the Firestore client
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the Firestore client
     */
    public function getClient(): Client
    {
        if (!$this->client) {
            $this->client = app(Client::class);
        }
        return $this->client;
    }

    /**
     * Get the Firestore collection for this model
     */
    public function getCollection(): FirestoreCollection
    {
        return $this->getClient()->collection($this->collection);
    }

    /**
     * Fill the model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (!in_array($key, $this->guarded)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Save the model to Firestore
     */
    public function save(): bool
    {
        try {
            if ($this->timestamps) {
                $now = Carbon::now()->format($this->dateFormat);
                if (!$this->getExists()) {
                    $this->setAttribute('createdAt', $now);
                }
                $this->setAttribute('updatedAt', $now);
            }

            $attributes = $this->getAttributes();

            if ($this->getExists()) {
                if (!$this->getId()) {
                    return false;
                }
                $document = $this->getCollection()->document($this->getId());
                return $document->update($attributes);
            }

            $document = $this->getCollection()->add($attributes, null);
            if ($document && method_exists($document, 'getId')) {
                $this->setId($document->getId());
                $this->setExists(true);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete the model from Firestore
     */
    public function delete(): bool
    {
        try {
            if (!$this->getExists() || !$this->getId()) {
                return false;
            }

            $document = $this->getCollection()->document($this->getId());

            if (static::$globalSoftDeletes) {
                $now = Carbon::now()->format($this->dateFormat);
                $this->setAttribute('deletedAt', $now);
                if ($this->timestamps) {
                    $this->setAttribute('updatedAt', $now);
                }
                return $document->update($this->getAttributes());
            }

            return $document->delete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert the model to an array
     */
    public function toArray(): array
    {
        $attributes = $this->getAttributes();
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }
        return $attributes;
    }

    /**
     * Convert the model to JSON
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create a new model instance
     */
    public static function create(array $attributes): self
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Set whether to use timestamps globally
     */
    public static function setGlobalTimestamps(bool $value): void
    {
        static::$globalTimestamps = $value;
    }

    /**
     * Set whether to use soft deletes globally
     */
    public static function setGlobalSoftDeletes(bool $value): void
    {
        static::$globalSoftDeletes = $value;
    }

    /**
     * Set the global date format
     */
    public static function setGlobalDateFormat(string $format): void
    {
        static::$globalDateFormat = $format;
    }

    /**
     * Set the global date columns
     */
    public static function setGlobalDateColumns(array $columns): void
    {
        static::$globalDateColumns = $columns;
    }

    /**
     * Get a model by its ID
     */
    public static function find(string $id): ?self
    {
        $model = new static();
        $document = $model->getCollection()->document($id);
        if (!$document) {
            return null;
        }
        $model->fill($document->data());
        $model->setId($id);
        $model->setExists(true);
        return $model;
    }

    /**
     * Create a new query for this model
     */
    public function query()
    {
        return $this->getCollection()->query();
    }

    /**
     * Update the model's attributes
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    /**
     * Get a model attribute
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Set a model attribute
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if an attribute exists
     */
    public function __isset(string $key): bool
    {
        return $this->getAttribute($key) !== null;
    }

    /**
     * Get all models from the collection
     */
    public static function all(): array
    {
        $model = new static();
        $documents = $model->getCollection()->list()->getDocuments();
        $models = [];
        foreach ($documents as $document) {
            $model = new static($document->data());
            $model->setId($document->id());
            $model->setExists(true);
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Get or create a model
     */
    public static function firstOrCreate(array $attributes, array $values = []): self
    {
        $model = new static();
        $query = $model->query();
        foreach ($attributes as $key => $value) {
            $query->where($key, '==', $value);
        }
        $documents = $query->limit(1)->get()->getDocuments();
        if (count($documents) > 0) {
            $model->fill($documents[0]->data());
            $model->setId($documents[0]->id());
            $model->setExists(true);
            return $model;
        }
        return static::create(array_merge($attributes, $values));
    }

    /**
     * Create or update a model
     */
    public static function updateOrCreate(array $attributes, array $values = []): self
    {
        $model = static::firstOrCreate($attributes);
        $model->update($values);
        return $model;
    }

    /**
     * Refresh the model from Firestore
     */
    public function refresh(): self
    {
        if ($this->getExists()) {
            $document = $this->getCollection()->document($this->getId());
            if ($document) {
                $this->fill($document->data());
            }
        }
        return $this;
    }

    /**
     * Get a fresh instance of the model from Firestore
     */
    public function fresh(): ?self
    {
        if ($this->getExists()) {
            return static::find($this->getId());
        }
        return null;
    }

    /**
     * Clone the model into a new instance
     */
    public function replicate(array $except = []): self
    {
        $attributes = Arr::except($this->getAttributes(), $except);
        return new static($attributes);
    }

    /**
     * Get the model's attributes
     */
    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->castAttribute($key, $value);
        }
        return $attributes;
    }

    /**
     * Set a given attribute on the model
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Cast an attribute to a native PHP type
     */
    protected function castAttribute(string $key, $value)
    {
        if (in_array($key, $this->dates)) {
            return $this->asDateTime($value);
        }
        return $value;
    }

    /**
     * Convert a value to a DateTime instance
     */
    protected function asDateTime($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        return Carbon::parse($value);
    }

    /**
     * Add timestamps to the model
     */
    protected function addTimestamps(): void
    {
        $now = Carbon::now()->format($this->dateFormat);
        if (!$this->getExists()) {
            $this->setAttribute('createdAt', $now);
        }
        $this->setAttribute('updatedAt', $now);
    }

    /**
     * Get the created at column name.
     */
    public function getCreatedAtColumn(): string
    {
        return static::$globalDateColumns['created_at'];
    }

    /**
     * Get the updated at column name.
     */
    public function getUpdatedAtColumn(): string
    {
        return static::$globalDateColumns['updated_at'];
    }

    /**
     * Get the deleted at column name.
     */
    public function getDeletedAtColumn(): string
    {
        return static::$globalDateColumns['deleted_at'];
    }

    /**
     * Check if the model uses timestamps.
     */
    public function usesTimestamps(): bool
    {
        return $this->timestamps ?? static::$globalTimestamps;
    }

    /**
     * Check if the model uses soft deletes.
     */
    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes ?? static::$globalSoftDeletes;
    }
}
