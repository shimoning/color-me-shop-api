<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 受注で使用されたショップクーポン情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class SaleShopCoupon extends Entity
{
    protected int $id;
    protected string $name;
    protected string $code;

    /**
     * ショップクーポンID
     * @return int
     */
    public function getId(): int
    {
        $this->assertFieldInitialized('id');
        return $this->id;
    }

    /**
     * クーポン名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * クーポンコード
     * @return string
     */
    public function getCode(): string
    {
        $this->assertFieldInitialized('code');
        return $this->code;
    }
}
