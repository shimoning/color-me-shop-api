<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

class PrivateShadowingPrivateFieldEntity extends ShadowedPrivateFieldParentEntity
{
    private string $name;

    public function getChildName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }
}
