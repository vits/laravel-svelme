<?php

namespace Vits\Svelme\Http\Filters;

use Closure;
use Illuminate\Support\Facades\Request;

class BaseFilter
{
    public string $type = 'base';
    public bool $hasValue = false;
    public mixed $value = null;
    public Closure|null $condition = null;
    public Closure|null $apply = null;

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

    public function toArray($options = [])
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'label' => $this->label,
            'value' => $this->value,
            'default' => $this->default
        ];
    }

    public function skip($options = [])
    {
        if (!$this->condition) {
            return false;
        }

        return !($this->condition)($options);
    }

    protected function normalized(mixed $value): mixed
    {
        return $value;
    }

    public function updateQuery($query, $options = [])
    {
        $value = $this->hasValue ? $this->value : $this->default;

        if ($this->apply) {
            ($this->apply)($query, $value, $options);
        } else if ($this->hasValue) {
            $query->where($this->name, $value);
        }

        return $query;
    }
}
