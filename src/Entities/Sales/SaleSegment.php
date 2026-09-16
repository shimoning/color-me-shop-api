<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 分割された受注の情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class SaleSegment extends Entity
{
    protected int $id;
    protected string $name;
    protected int $parentSaleId;
    protected bool $splitted;
    protected int $productTotalPrice;
    protected int $deliveryTotalCharge;
    protected int $totalPrice;
    protected int $noshiTotalCharge;
    protected int $cardTotalCharge;
    protected int $wrappingTotalCharge;

    /** @var list<int> */
    protected array $siblingsSaleIds;

    /**
     * 分割された受注内のID
     * @return int
     */
    public function getId(): int
    {
        $this->assertFieldInitialized('id');
        return $this->id;
    }

    /**
     * 区分名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 親受注のID
     * @return int
     */
    public function getParentSaleId(): int
    {
        $this->assertFieldInitialized('parentSaleId');
        return $this->parentSaleId;
    }

    /**
     * 該当の受注が分割されているか否か
     * @return bool
     */
    public function isSplitted(): bool
    {
        $this->assertFieldInitialized('splitted');
        return $this->splitted;
    }

    /**
     * 分割された受注の商品の合計金額
     * @return int
     */
    public function getProductTotalPrice(): int
    {
        $this->assertFieldInitialized('productTotalPrice');
        return $this->productTotalPrice;
    }

    /**
     * 分割された受注の配送料の合計
     * @return int
     */
    public function getDeliveryTotalCharge(): int
    {
        $this->assertFieldInitialized('deliveryTotalCharge');
        return $this->deliveryTotalCharge;
    }

    /**
     * 分割された受注金額の総計
     * @return int
     */
    public function getTotalPrice(): int
    {
        $this->assertFieldInitialized('totalPrice');
        return $this->totalPrice;
    }

    /**
     * 分割された受注の熨斗料金の合計
     * @return int
     */
    public function getNoshiTotalCharge(): int
    {
        $this->assertFieldInitialized('noshiTotalCharge');
        return $this->noshiTotalCharge;
    }

    /**
     * 分割された受注のメッセージカード料金の合計
     * @return int
     */
    public function getCardTotalCharge(): int
    {
        $this->assertFieldInitialized('cardTotalCharge');
        return $this->cardTotalCharge;
    }

    /**
     * 分割された受注のラッピング料金の合計
     * @return int
     */
    public function getWrappingTotalCharge(): int
    {
        $this->assertFieldInitialized('wrappingTotalCharge');
        return $this->wrappingTotalCharge;
    }

    /**
     * 分割された受注ID
     * @return list<int>
     */
    public function getSiblingsSaleIds(): array
    {
        $this->assertFieldInitialized('siblingsSaleIds');
        return $this->siblingsSaleIds;
    }
}
