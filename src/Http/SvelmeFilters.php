<?php

namespace Vits\Svelme\Http;

class SvelmeFilters
{
    protected $filterInstances = [];

    public function __construct(
        public array $options = []
    ) {
        $this->filterInstances = $this->filters();
    }

    public function apply($query, $options = [])
    {
        foreach ($this->filterInstances as $filter) {
            if ($filter->skip([...$this->options, ...$options])) {
                continue;
            }

            $filter->updateQuery($query, [...$this->options, ...$options]);
        }

        return $query;
    }

    public function toArray($options = [])
    {
        $data = [];

        foreach ($this->filterInstances as $filter) {
            if ($filter->skip([...$this->options, ...$options])) {
                continue;
            }

            $data[] = $filter->toArray([...$this->options, ...$options]);
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
