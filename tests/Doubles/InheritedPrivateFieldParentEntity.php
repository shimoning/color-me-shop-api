<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 継承された private フィールドの検証用テストダブル。
 */
class InheritedPrivateFieldParentEntity extends Entity
{
    private string $name;

    public function getName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }

    public function assertUnknownField(): void
    {
        $this->assertFieldInitialized('unknownField');
    }
}
