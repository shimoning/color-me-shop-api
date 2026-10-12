<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * カードブランド
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
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
        $this->assertFieldInitialized('id');

        return $this->id;
    }

    /**
     * ブランド名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');

        return $this->name;
    }
}
