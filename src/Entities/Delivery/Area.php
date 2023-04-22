<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\Prefecture;

/**
 * 都道府県ごとの送料設定
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
 */
class Area extends Entity
{
    public const OBJECT_FIELDS = [
        'prefId' => [
            'enum' => Prefecture::class,
        ],
    ];

    protected Prefecture $prefId;
    protected string $prefName;
    protected int $charge;

    /**
     * 都道府県の通し番号
     * @return Prefecture
     */
    public function getPrefId(): Prefecture
    {
        return $this->prefId;
    }

    /**
     * 都道府県名
     * @return string
     */
    public function getPrefName(): string
    {
        return $this->prefName;
    }

    /**
     * 配送料
     * @return int
     */
    public function getCharge(): int
    {
        return $this->charge;
    }
}
