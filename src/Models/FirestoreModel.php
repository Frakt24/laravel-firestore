<?php

namespace Frakt24\LaravelPHPFirestore\Models;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreCollection;
use Frakt24\LaravelPHPFirestore\FirestoreService;
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
    protected array $dates = ['createdAt', 'updatedAt'];
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

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get the Firestore collection for this model
     */
    public function getCollection(): FirestoreCollection
    {
        return App::make(FirestoreService::class)->collection($this->collection);
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
        if ($this->timestamps) {
            $this->addTimestamps();
        }

        if ($this->exists) {
            $this->getCollection()->update($this->id, $this->getAttributes());
        } else {
            $document = $this->getCollection()->add($this->getAttributes(), $this->id);
            $this->id = $document->id();
            $this->exists = true;
        }

        return true;
    }

    /**
     * Delete the model from Firestore
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        return $this->getCollection()->delete($this->id);
    }

    /**
     * Get the model's ID
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set the model's ID
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
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

        if ($this->id) {
            $attributes['id'] = $this->id;
        }

        return $attributes;
    }

    /**
     * Find a model by its ID
     */
    public static function find(string $id): ?static
    {
        try {
            $document = (new static)->getCollection()->document($id);
            $model = new static($document->data());
            $model->setId($id);
            $model->exists = true;
            return $model;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create a new query for this model
     */
    public static function query(): FirestoreModelQuery
    {
        return new FirestoreModelQuery(new static);
    }

    /**
     * Create a new model instance
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
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
        return isset($this->attributes[$key]);
    }

    /**
     * Convert the model to JSON serializable data
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get all models from the collection
     */
    public static function all(): Collection
    {
        return static::query()->get();
    }

    /**
     * Get or create a model
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        if (!is_null($instance = static::query()->where($attributes)->first())) {
            return $instance;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * Create or update a model
     */
    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $instance = static::firstOrCreate($attributes);
        $instance->fill($values)->save();
        return $instance;
    }

    /**
     * Refresh the model from Firestore
     */
    public function refresh(): self
    {
        if (!$this->exists) {
            return $this;
        }

        $fresh = static::find($this->id);
        
        if (!$fresh) {
            return $this;
        }

        $this->fill($fresh->getAttributes());
        return $this;
    }

    /**
     * Get a fresh instance of the model from Firestore
     */
    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        return static::find($this->id);
    }

    /**
     * Clone the model into a new instance
     */
    public function replicate(array $except = []): static
    {
        $attributes = Arr::except($this->getAttributes(), $except);
        return new static($attributes);
    }

    /**
     * Get the model's attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set a given attribute on the model
     */
    public function setAttribute(string $key, $value): void
    {
        // Handle date casting
        if (in_array($key, $this->dates)) {
            $value = $this->asDateTime($value);
        }

        // Handle custom casts
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Cast an attribute to a native PHP type
     */
    protected function castAttribute(string $key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
                return (array) $value;
            case 'object':
                return (object) $value;
            case 'collection':
                return collect($value);
            default:
                return $value;
        }
    }

    /**
     * Convert a value to a DateTime instance
     */
    protected function asDateTime($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return $value;
    }

    /**
     * Set the global timestamps setting.
     */
    public static function setGlobalTimestamps(bool $value): void
    {
        static::$globalTimestamps = $value;
    }

    /**
     * Set the global soft deletes setting.
     */
    public static function setGlobalSoftDeletes(bool $value): void
    {
        static::$globalSoftDeletes = $value;
    }

    /**
     * Set the global date format.
     */
    public static function setGlobalDateFormat(string $format): void
    {
        static::$globalDateFormat = $format;
    }

    /**
     * Set the global date columns.
     */
    public static function setGlobalDateColumns(array $columns): void
    {
        static::$globalDateColumns = $columns;
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
