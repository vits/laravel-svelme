<?php

namespace Vits\Svelme\Http\Filters;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Support\Carbon;

class DateFilter extends BaseFilter
{
    public string $type = 'date';

    public function __construct(
        public string $name,
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
        $label = null,
        $default = null,
        $apply = null,
        $condition = null,
        $normalize = null,
        $value = null,
    ) {
        return new self($name, $label, $default, $apply, $condition, $normalize, $value);
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

    protected function normalized(mixed $value): mixed
    {
        try {
            $date = Carbon::parse($value)->format('Y-m-d');
        } catch (InvalidFormatException $e) {
            abort(400);
        }
        if ($date !== $value) {
            abort(400);
        }

        return $value;
    }
}
