<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 重量による送料設定
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
 */
class Weight extends Entity
{
    public const OBJECT_FIELDS = [
        'areas' => [
            'array' => true,
            'entity' => Area::class,
        ],
    ];

    protected int $weight;
    protected array $areas;

    /**
     * これ未満のグラム数の場合
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * 都道府県ごとの配送料
     * @return array<Area>
     */
    public function getAreas(): array
    {
        return $this->areas;
    }
}
