<?php

namespace Frakt24\LaravelPHPFirestore\Query;

class FirestoreAggregationQuery
{
    private $structuredQuery;
    private $aggregations = [];

    public function __construct(FirestoreStructuredQuery $structuredQuery)
    {
        $this->structuredQuery = $structuredQuery;
    }

    /**
     * Add a count aggregation
     *
     * @param string $alias Result alias
     * @return self
     */
    public function count(string $alias = 'count'): self
    {
        $this->aggregations[$alias] = ['count' => (object)[]];
        return $this;
    }

    /**
     * Add a sum aggregation
     *
     * @param string $field Field to sum
     * @param string $alias Result alias
     * @return self
     */
    public function sum(string $field, string $alias): self
    {
        $this->aggregations[$alias] = ['sum' => ['field' => ['fieldPath' => $field]]];
        return $this;
    }

    /**
     * Add an average aggregation
     *
     * @param string $field Field to average
     * @param string $alias Result alias
     * @return self
     */
    public function avg(string $field, string $alias): self
    {
        $this->aggregations[$alias] = ['avg' => ['field' => ['fieldPath' => $field]]];
        return $this;
    }

    /**
     * Build the aggregation query
     *
     * @return array
     */
    public function build(): array
    {
        return [
            'structuredQuery' => $this->structuredQuery->build(),
            'aggregations' => $this->aggregations
        ];
    }
}
