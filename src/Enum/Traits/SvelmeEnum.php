<?php

namespace Vits\Svelme\Enum\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;

trait SvelmeEnum
{
    /**
     * Returns translated enum label string.
     *
     * @return string
     * @throws BindingResolutionException
     */
    public function label(): string
    {
        return __('app.enums.' . Str::snake(class_basename($this)) . '.' . $this->value);
    }

    /**
     * Return array of all value strings.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns array of available options with translated labels.
     *
     * @return array
     */
    public static function toOptions(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
