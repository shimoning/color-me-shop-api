<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

class ProtectedShadowingPrivateFieldEntity extends ShadowedPrivateFieldParentEntity
{
    protected string $name;

    public function getChildName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }
}
