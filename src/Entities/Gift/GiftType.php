<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * ギフトの種類。
 */
class GiftType extends Entity
{
    protected string $name;
    protected int $charge;

    /**
     * 種類名。
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 料金。
     */
    public function getCharge(): int
    {
        $this->assertFieldInitialized('charge');
        return $this->charge;
    }
}
