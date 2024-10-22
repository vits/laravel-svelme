<?php

namespace Vits\Svelme\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Inertia\Inertia;

trait IndexQuery
{
    public function indexQuery(Builder|Relation|string $query): Builder|Relation
    {
        if (is_string($query)) {
            $query = call_user_func($query . '::query');
        }

        $options = $this->getIndexQueryOptions();

        if ($order = request('orderby')) {
            if (!in_array($order, $options['sortable'], true)) {
                $order = null;
            }
        }

        if (!$order && $options['sortable']) {
            $order = $options['sortable'][0];
        }

        if ($order) {
            $options['orderby'] = $order;
            $options['desc'] = (bool) request('desc');

            $query = $query->orderBy($order, request('desc') ? 'desc' : 'asc');
        }

        if ($options['searchable'] && $search = request('search')) {
            $scopeName = property_exists(
                $this,
                'indexQuerySearchScope'
            ) && $this->indexQuerySearchScope ? $this->indexQuerySearchScope : 'simpleSearch';

            $query = $query->{$scopeName}($search);
            $options['search'] = $search;
        }

        Inertia::share('index_query_options', $options);

        return $query;
    }

    /**
     * Prepares normalized array of index query options.
     *
     * @return array
     */
    protected function getIndexQueryOptions()
    {
        $options = [
            'sortable' => [],
            'searchable' => false,
            'filters' => [],
        ];

        if (
            property_exists($this, 'indexQueryOptions')
            && is_array($this->indexQueryOptions)
        ) {
            // take only known options
            if (array_key_exists('searchable', $this->indexQueryOptions)) {
                $options['searchable'] = $this->indexQueryOptions['searchable'];
            }
            if (array_key_exists('sortable', $this->indexQueryOptions)) {
                $options['sortable'] = $this->indexQueryOptions['sortable'];
            }
        }

        if (!is_bool($options['searchable'])) {
            $options['searchable'] = (bool) $options['searchable'];
        }

        if (is_string($options['sortable'])) {
            $options['sortable'] = [$options['sortable']];
        }

        if (method_exists($this, 'indexFiltersArray')) {
            $options['filters'] = $this->indexFiltersArray();
        }

        return $options;
    }
}
