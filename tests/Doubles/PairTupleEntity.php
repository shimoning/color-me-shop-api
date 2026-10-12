<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

class PairTupleEntity extends Entity
{
    public static function isPairTuple(mixed $value): bool
    {
        return parent::isPairTuple($value);
    }
}
