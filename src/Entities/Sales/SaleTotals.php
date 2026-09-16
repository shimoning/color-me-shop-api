<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 税率別の受注金額
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class SaleTotals extends Entity
{
    protected int $normalTaxAmount;
    protected int $reducedTaxAmount;
    protected int $discountAmountForNormalTax;
    protected int $discountAmountForReducedTax;
    protected int $totalPriceWithNormalTax;
    protected int $totalPriceWithReducedTax;

    /**
     * 通常税率(10%)の消費税
     * @return int
     */
    public function getNormalTaxAmount(): int
    {
        $this->assertFieldInitialized('normalTaxAmount');
        return $this->normalTaxAmount;
    }

    /**
     * 軽減税率(8%)の消費税
     * @return int
     */
    public function getReducedTaxAmount(): int
    {
        $this->assertFieldInitialized('reducedTaxAmount');
        return $this->reducedTaxAmount;
    }

    /**
     * 通常税率(10%)の適用金額を対象とした割引額
     * @return int
     */
    public function getDiscountAmountForNormalTax(): int
    {
        $this->assertFieldInitialized('discountAmountForNormalTax');
        return $this->discountAmountForNormalTax;
    }

    /**
     * 軽減税率(8%)の適用金額を対象とした割引額
     * @return int
     */
    public function getDiscountAmountForReducedTax(): int
    {
        $this->assertFieldInitialized('discountAmountForReducedTax');
        return $this->discountAmountForReducedTax;
    }

    /**
     * 通常税率(10%)の適用金額の税込合計額
     * @return int
     */
    public function getTotalPriceWithNormalTax(): int
    {
        $this->assertFieldInitialized('totalPriceWithNormalTax');
        return $this->totalPriceWithNormalTax;
    }

    /**
     * 軽減税率(8%)の適用金額の税込合計額
     * @return int
     */
    public function getTotalPriceWithReducedTax(): int
    {
        $this->assertFieldInitialized('totalPriceWithReducedTax');
        return $this->totalPriceWithReducedTax;
    }
}
