<?php

namespace Vits\Svelme\Http\Filters;

use Illuminate\Support\Facades\Request;

class BaseFilter
{
    public bool $hasValue = false;
    public mixed $value = null;
    public string $type = 'base';

    public function __construct(
        public string $name,
        public string|null $label,
        public mixed $default,
    ) {
        if (!$label) {
            $this->label = $name;
        }

        $this->hasValue = Request::has($name);
        if ($this->hasValue) {
            $this->value = $this->normalized(Request::get($name));
        }
    }

    public function toArray()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'label' => $this->label,
            'value' => $this->value,
            'default' => $this->default
        ];
    }

    protected function normalized(mixed $value): mixed
    {
        return $value;
    }

    public function updateQuery($query, $options = [])
    {
        return $query;
    }
}
