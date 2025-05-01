<?php

namespace Frakt24\LaravelPHPFirestore\Models;

use Frakt24\LaravelPHPFirestore\Contracts\FirestoreQuery;
use Illuminate\Support\Collection;

class FirestoreModelQuery
{
    private FirestoreModel $model;
    private FirestoreQuery $query;
    private array $eagerLoad = [];

    public function __construct(FirestoreModel $model)
    {
        $this->model = $model;
        $this->query = $model->getCollection()->query();
    }

    /**
     * Add a where clause to the query
     */
    public function where(string $field, string $operator, $value): self
    {
        $this->query->where($field, $operator, $value);
        return $this;
    }

    /**
     * Add an orderBy clause to the query
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->query->orderBy($field, $direction);
        return $this;
    }

    /**
     * Add a limit clause to the query
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Execute the query and get the results
     */
    public function get(): Collection
    {
        $documents = $this->query->get();
        return collect($documents)->map(function ($document) {
            $model = new ($this->model)($document->data());
            $model->setId($document->id());
            $model->exists = true;
            return $model;
        });
    }

    /**
     * Get the first result from the query
     */
    public function first(): ?FirestoreModel
    {
        $result = $this->limit(1)->get();
        return $result->first();
    }

    /**
     * Add a whereIn clause to the query
     */
    public function whereIn(string $field, array $values): self
    {
        $this->query->whereIn($field, $values);
        return $this;
    }

    /**
     * Add a whereArrayContains clause to the query
     */
    public function whereArrayContains(string $field, $value): self
    {
        $this->query->whereArrayContains($field, $value);
        return $this;
    }

    /**
     * Add a startAt cursor to the query
     */
    public function startAt($document): self
    {
        $this->query->startAt($document);
        return $this;
    }

    /**
     * Add a startAfter cursor to the query
     */
    public function startAfter($document): self
    {
        $this->query->startAfter($document);
        return $this;
    }

    /**
     * Add an endAt cursor to the query
     */
    public function endAt($document): self
    {
        $this->query->endAt($document);
        return $this;
    }

    /**
     * Add an endBefore cursor to the query
     */
    public function endBefore($document): self
    {
        $this->query->endBefore($document);
        return $this;
    }

    /**
     * Get a paginated result set
     */
    public function paginate(int $perPage = 15, ?string $pageName = 'page', int $page = null): Collection
    {
        $page = $page ?: request()->input($pageName, 1);
        $offset = ($page - 1) * $perPage;

        return $this->limit($perPage)
            ->get()
            ->values();
    }

    /**
     * Count the number of results
     */
    public function count(): int
    {
        return $this->get()->count();
    }

    /**
     * Check if any results exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get the underlying query object
     */
    public function getQuery(): FirestoreQuery
    {
        return $this->query;
    }

    /**
     * Add eager loading of relationships
     */
    public function with(string|array $relations): self
    {
        $relations = is_string($relations) ? func_get_args() : $relations;
        $this->eagerLoad = array_merge($this->eagerLoad, $relations);
        return $this;
    }

    /**
     * Get a chunk of results
     */
    public function chunk(int $size, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->forPage($page, $size)->get();
            $countResults = $results->count();

            if ($countResults === 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while ($countResults === $size);

        return true;
    }

    /**
     * Set the page and size for pagination
     */
    public function forPage(int $page, int $size): self
    {
        return $this->limit($size)->startAfter(($page - 1) * $size);
    }

    /**
     * Get a random result from the query
     */
    public function random(): ?FirestoreModel
    {
        $results = $this->get();
        return $results->isEmpty() ? null : $results->random();
    }

    /**
     * Get results as key-value pairs
     */
    public function pluck(string $value, ?string $key = null): Collection
    {
        $results = $this->get();
        return $results->pluck($value, $key);
    }

    /**
     * Get results in chunks and process them
     */
    public function each(callable $callback, int $chunkSize = 100): bool
    {
        return $this->chunk($chunkSize, function ($results) use ($callback) {
            foreach ($results as $result) {
                if ($callback($result) === false) {
                    return false;
                }
            }
            return true;
        });
    }
}
