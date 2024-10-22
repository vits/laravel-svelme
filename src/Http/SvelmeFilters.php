<?php

namespace Vits\Svelme\Http;

class SvelmeFilters
{
    protected $filterInstances = [];

    public function __construct()
    {
        $this->filterInstances = $this->filters();
    }

    public function apply($query, $options)
    {
        foreach ($this->filterInstances as $filter) {
            $filter->updateQuery($query, $options);
        }

        return $query;
    }

    public function toArray($options)
    {
        $data = [];

        foreach ($this->filterInstances as $filter) {
            $data[] = $filter->toArray();
        }

        return $data;
    }

    public function getByName($name)
    {
        return collect($this->filterInstances)->where('name', $name)->first();
    }

    protected function filters(): array
    {
        return [];
    }
}
