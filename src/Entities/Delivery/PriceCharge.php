<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 価格による配送料区分
 */
class PriceCharge extends Entity
{
    protected int $upperLimit;
    protected int $charge;

    /**
     * 区分の上限金額
     */
    public function getUpperLimit(): int
    {
        $this->assertFieldInitialized('upperLimit');

        return $this->upperLimit;
    }

    /**
     * 区分の配送料
     */
    public function getCharge(): int
    {
        $this->assertFieldInitialized('charge');

        return $this->charge;
    }
}
