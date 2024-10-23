<?php

namespace Vits\Svelme\Http\Filters;

use Closure;

class OptionsFilter extends BaseFilter
{
    public string $type = 'options';

    public function __construct(
        public string $name,
        public array $items,
        public string|null $label,
        public mixed $default,
        public Closure|null $apply,
        public Closure|null $condition,
        public Closure|null $normalize,
    ) {
        parent::__construct($name, $label, $default);
    }

    public static function make(
        $name,
        $items,
        $label = null,
        $default = null,
        $apply = null,
        $condition = null,
        $normalize = null,
        $value = null,
    ) {
        return new self($name, $items, $label, $default, $apply, $condition, $normalize, $value);
    }

    // public function updateQuery($query, $options = [])
    // {
    //     $value = $this->hasValue ? $this->value : $this->default;

    //     if ($this->apply) {
    //         ($this->apply)($query, $value, $options);
    //     } else if ($this->hasValue) {
    //         $query->where($this->name, $value);
    //     }

    //     return $query;
    // }

    public function toArray($options = [])
    {
        return [
            ...parent::toArray(),
            'items' => $this->items,
        ];
    }

    protected function normalized(mixed $value): mixed
    {
        $exists = collect($this->items)->where('value', $value)->first();

        if ($exists) {
            return $exists['value'];
        }

        abort(400);
    }
}
