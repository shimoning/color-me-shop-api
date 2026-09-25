<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 顧客ショップポイントの増減 (POST /v1/customers/{customer_id}/points) の応答。
 *
 * 他の顧客 API と異なり、応答は `customer` などのキーで包まれず `customer_id` と `points` を
 * トップレベルに持つ。`points` は増減後の保有ポイント数である。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Points extends Entity
{
    protected int $customerId;
    protected int $points;

    /**
     * 顧客ID
     * @return int
     */
    public function getCustomerId(): int
    {
        $this->assertFieldInitialized('customerId');
        return $this->customerId;
    }

    /**
     * 増減後の保有ポイント数
     * @return int
     */
    public function getPoints(): int
    {
        $this->assertFieldInitialized('points');
        return $this->points;
    }
}
