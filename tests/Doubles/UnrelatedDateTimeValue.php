<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Values\Value;

class UnrelatedDateTimeValue extends \DateTimeImmutable implements Value
{
    public function validate(mixed $value): bool
    {
        return true;
    }

    public function get(): string
    {
        return $this->format('Y-m-d H:i:s');
    }
}
