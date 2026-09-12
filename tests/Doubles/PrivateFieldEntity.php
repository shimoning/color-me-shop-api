<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * private 宣言フィールドの hydrate 検証用テストダブル。
 */
class PrivateFieldEntity extends Entity
{
    private string $name;

    public function getName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }
}
