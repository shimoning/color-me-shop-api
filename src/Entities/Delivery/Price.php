<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 注文金額による配送料の区分。API の `[上限, 配送料]` の組に意味付きの名前を与える。
 *
 * @see docs/api-delivery-charge-observation.md
 */
class Price extends Entity
{
    protected int $upperLimit;
    protected int $charge;

    /**
     * 区分の上限金額。この金額はこの区分に含まれない (未満)。
     *
     * 公式 OpenAPI との差分: 「以下」と説明しているが、実 API の設定では未満である (2026-10-05)。
     */
    public function getUpperLimit(): int
    {
        $this->assertFieldInitialized('upperLimit');

        return $this->upperLimit;
    }

    /**
     * この区分の配送料
     */
    public function getCharge(): int
    {
        $this->assertFieldInitialized('charge');

        return $this->charge;
    }
}
