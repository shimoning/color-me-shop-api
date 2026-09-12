<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 欠損と不正値の基底契約を検証するためのテストダブル。
 */
class RequiredEntity extends Entity
{
    protected string $name;
    protected int $count;
    protected ?string $description;

    public function getName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }

    public function getDescription(): ?string
    {
        $this->assertFieldInitialized('description');

        return $this->description;
    }
}
