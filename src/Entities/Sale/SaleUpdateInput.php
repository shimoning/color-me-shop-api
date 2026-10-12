<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Constants\PointState;

/**
 * 受注更新データ
 *
 * 受注 ID は Services\Sale::update() / Client::updateSale() の引数として渡す。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
 */
class SaleUpdateInput extends Entity implements RequestEntity
{
    const FIELD_TYPES = [
        'pointState' => [
            'enum' => PointState::class,
        ],
        'saleDeliveries' => [
            'array' => true,
            'entity' => DeliveryUpdateInput::class,
        ],
    ];

    protected bool $paid;
    protected PointState $pointState;
    /** @var list<DeliveryUpdateInput> */
    protected array $saleDeliveries;

    /**
     * 入金済みであるか否か
     * @return bool
     * @deprecated 0.31.0 送信内容は toArrayRecursive() で確かめること。
     */
    public function isPaid(): bool
    {
        $this->assertFieldInitialized('paid');
        return $this->paid;
    }

    /**
     * 入金済みであるか否かを設定
     * @param bool $paid
     * @return void
     * @deprecated 0.31.0 コンストラクタの配列で指定すること。
     */
    public function setPaid(bool $paid)
    {
        $this->paid = $paid;
        $this->markRequestField('paid');
    }

    /**
     * ショップポイント付与状態
     * @return PointState
     * @deprecated 0.31.0 送信内容は toArrayRecursive() で確かめること。
     */
    public function getPointState(): PointState
    {
        $this->assertFieldInitialized('pointState');
        return $this->pointState;
    }

    /**
     * ショップポイント付与状態を設定
     * @param PointState $pointState
     * @return void
     * @deprecated 0.31.0 コンストラクタの配列で指定すること。
     */
    public function setPointState(PointState $pointState)
    {
        $this->pointState = $pointState;
        $this->markRequestField('pointState');
    }

    /**
     * お届け先
     * @return list<DeliveryUpdateInput>
     * @deprecated 0.31.0 送信内容は toArrayRecursive() で確かめること。
     */
    public function getSaleDeliveries(): array
    {
        $this->assertFieldInitialized('saleDeliveries');
        return $this->saleDeliveries;
    }

    /**
     * お届け先を設定
     * @param list<DeliveryUpdateInput> $saleDeliveries
     * @return void
     * @deprecated 0.31.0 コンストラクタの配列で指定すること。
     */
    public function setSaleDeliveries($saleDeliveries)
    {
        $this->assertRequestArrayFieldIsList('saleDeliveries', $saleDeliveries);
        $this->saleDeliveries = $saleDeliveries;
        $this->markRequestField('saleDeliveries');
    }

    /**
     * 受注データを更新用データへ変換する。
     *
     * @param Sale $sale 変換元の受注データ
     * @return self
     * @deprecated 0.31.0 送りたい項目だけをコンストラクタの配列で渡すこと。
     */
    static public function convert(Sale $sale): self
    {
        return new self([
            'paid' => $sale->isPaid(),
            'point_state' => $sale->getPointState()->value,
            'sale_deliveries' => array_map(function ($sd) {
                return $sd->toArrayRecursive();
            }, $sale->getSaleDeliveries()),
        ]);
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SaleUpdateInput::class);
