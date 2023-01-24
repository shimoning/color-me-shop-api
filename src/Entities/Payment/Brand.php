<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * カードブランド
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 */
class Brand extends Entity
{
    protected int $id;
    protected string $name;

    /**
     * ブランドID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * ブランド名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
