<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\PointState;

/**
 * 受注更新データ
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
 */
class SaleUpdater extends Entity
{
    const OBJECT_FIELDS = [
        'pointState' => [
            'enum' => PointState::class,
        ],
        'saleDeliveries' => [
            'array' => true,
            'entity' => SaleDeliveryUpdater::class,
        ],
    ];

    protected int $id;
    protected bool $paid;
    protected PointState $pointState;
    protected array $saleDeliveries;

    /**
     * 売上ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 入金済みであるか否か
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->paid;
    }

    /**
     * 入金済みであるか否かを設定
     * @param bool $paid
     */
    public function setPaid(bool $paid)
    {
        $this->paid = $paid;
    }

    /**
     * ショップポイント付与状態
     * @return PointState
     */
    public function getPointState(): PointState
    {
        return $this->pointState;
    }

    /**
     * ショップポイント付与状態を設定
     * @param PointState $pointState
     */
    public function setPointState(PointState $pointState)
    {
        $this->pointState = $pointState;
    }

    /**
     * お届け先
     * @return array<SaleDelivery>
     */
    public function getSaleDeliveries(): array
    {
        return $this->saleDeliveries;
    }

    /**
     * お届け先を設定
     * @param array<SaleDelivery>
     */
    public function setSaleDeliveries($saleDeliveries)
    {
        $this->saleDeliveries = $saleDeliveries;
    }

    static public function convert(Sale $sale): self
    {
        return new self([
            'id' => $sale->getId(),
            'paid' => $sale->isPaid(),
            'point_state' => $sale->getPointState()->value,
            'sale_deliveries' => array_map(function ($sd) {
                return $sd->toArrayRecursive();
            }, $sale->getSaleDeliveries()),
        ]);
    }
}
