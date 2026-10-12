<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

class StaticShadowingPrivateFieldEntity extends ShadowedPrivateFieldParentEntity
{
    public static string $name = 'original';
}
