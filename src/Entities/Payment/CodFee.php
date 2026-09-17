<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 代引き手数料の区分。API の2整数タプルに意味付きの名前を与える。
 */
class CodFee extends Entity
{
    protected int $upperLimit;
    protected int $fee;

    /**
     * 設定上、この区分に含まれない排他的上限
     */
    public function getUpperLimit(): int
    {
        $this->assertFieldInitialized('upperLimit');

        return $this->upperLimit;
    }

    /**
     * この区分に設定された手数料
     */
    public function getFee(): int
    {
        $this->assertFieldInitialized('fee');

        return $this->fee;
    }
}
