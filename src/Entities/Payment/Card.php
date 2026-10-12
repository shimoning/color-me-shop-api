<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * クレジットカードの設定情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
 */
class Card extends Entity
{
    const FIELD_TYPES = [
        'brands' => [
            'array' => true,
            'entity' => Brand::class,
        ],
    ];

    /** @var list<Brand> */
    protected array $brands;

    /**
     * 手数料が決済金額によって変わるか否か
     * @return list<Brand>
     */
    public function getBrands(): array
    {
        $this->assertFieldInitialized('brands');

        return $this->brands;
    }
}
