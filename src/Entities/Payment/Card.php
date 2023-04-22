<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * クレジットカードの設定情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 */
class Card extends Entity
{
    const OBJECT_FIELDS = [
        'brands' => [
            'array' => true,
            'entity' => Brand::class,
        ],
    ];

    protected array $brands;

    /**
     * 手数料が決済金額によって変わるか否か
     * @return array<Brand>
     */
    public function getBrands(): array
    {
        return $this->brands;
    }
}
