<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 子クラスの同名宣言に隠された親 private フィールドの検証用テストダブル。
 */
class ShadowedPrivateFieldParentEntity extends Entity
{
    private string $name;

    public function getParentName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }
}
