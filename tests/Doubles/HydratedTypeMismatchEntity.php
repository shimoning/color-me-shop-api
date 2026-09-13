<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 変換後の値が宣言型と一致しない経路を検証するテストダブル
 */
class HydratedTypeMismatchEntity extends Entity
{
    const OBJECT_FIELDS = [
        'child' => ['entity' => NestedEntity::class],
    ];

    protected array $child;
}
