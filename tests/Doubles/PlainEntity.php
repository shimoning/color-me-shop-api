<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * OBJECT_FIELDS を持たない素の Entity のテストダブル。
 * 実装と揃えるため、プロパティにはデフォルト値を与えていない。
 */
class PlainEntity extends Entity
{
    protected ?string $name;
    protected ?int $count;
    protected ?string $someLongName;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getSomeLongName(): ?string
    {
        return $this->someLongName;
    }
}
