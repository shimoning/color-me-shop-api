<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * Entity のネスト検証用テストダブル
 */
class NestedEntity extends Entity
{
    protected string $label;

    public function getLabel(): string
    {
        return $this->label;
    }
}
