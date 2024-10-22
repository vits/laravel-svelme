<?php

namespace Vits\Svelme\Http\Traits;

trait RememberParams
{
    use IndexQuery;

    public function rememberIndexParam(string $name, mixed $value): void
    {
        session()->put(static::class . '--index-' . $name, $value);
    }

    public function getRememberedIndexParam(string $name, mixed $default = null): mixed
    {
        return session(static::class . '--index-' . $name, $default);
    }

    public function getRememberedParamNames()
    {
        $names = [];

        if (property_exists($this, 'rememberIndexParams')) {
            $params = $this->rememberIndexParams;
            if (is_array($params)) {
                $names = array_filter($params, 'is_string');
            } else if (is_string($params)) {
                $names = array_filter(array_map('trim', explode(',', $params)));
            }
        }

        $options = $this->getIndexQueryOptions();
        if ($options['searchable']) {
            $names[] = 'search';
        }

        if ($options['sortable']) {
            $names = [
                ...$names,
                'orderby',
                'desc',
            ];
        }

        $names = [
            ...$names,
            ...array_map(fn($filter) => $filter['name'], $options['filters'])
        ];

        return array_values($names);
    }

    public function getRememberedParams()
    {
        $params = [];

        foreach ($this->getRememberedParamNames() as $name) {
            if ($value = $this->getRememberedIndexParam($name)) {
                $params[$name] = $value;
            }
        }

        return $params;
    }
}
