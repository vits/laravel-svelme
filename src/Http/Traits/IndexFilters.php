<?php

namespace Vits\Svelme\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Vits\Svelme\Http\SvelmeFilters;

trait IndexFilters
{
    protected SvelmeFilters|null|false $indexFiltersInstance = false;

    public function applyIndexFilters(Builder|Relation $query, array $options = []): Builder|Relation
    {
        $filters = $this->getIndexFilters();
        if ($filters) {
            return $filters->apply($query, $options);
        }

        return $query;
    }

    public function indexFiltersArray(array $options = []): array
    {
        $filters = $this->getIndexFilters();
        if ($filters) {
            return $filters->toArray($options);
        }

        return [];
    }

    public function getIndexFilters()
    {
        if ($this->indexFiltersInstance !== false) {
            return $this->indexFiltersInstance;
        }

        if (property_exists($this, 'indexFiltersClass')) {
            $options = [];
            if (method_exists($this, 'indexFiltersOptions')) {
                $options = $this->indexFiltersOptions();
            }

            $this->indexFiltersInstance = new $this->indexFiltersClass($options);
        } else {
            $this->indexFiltersInstance = null;
        }

        return $this->indexFiltersInstance;
    }
}
