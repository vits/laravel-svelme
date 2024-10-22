<?php

namespace Vits\Svelme\Tests\Support;

use Vits\Svelme\Enum\Traits\SvelmeEnum;

enum TestEnum: string
{
    use SvelmeEnum;

    case FIRST = 'first';
    case SECOND = 'second';
}
