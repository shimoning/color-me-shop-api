<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 決済設定
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 */
class Cod extends Entity
{
    protected bool $changeable;
    protected array $fees;
    protected int $feeMax;
    protected bool $changeableByTotal;

    /**
     * 手数料が決済金額によって変わるか否か
     * @return bool
     */
    public function getChangeable(): bool
    {
        return $this->changeable;
    }

    /**
     * 手数料が変わる決済金額の区分
     * [3000, 100]であれば、3000円以下の場合、手数料は100円であることを表す
     * @return array<int>
     */
    public function getFees(): array
    {
        return $this->fees;
    }

    /**
     * feesに設定されている区分以上の金額の場合の手数料
     * @return int
     */
    public function getFeeMax(): int
    {
        return $this->feeMax;
    }

    /**
     * 手数料計算に用いる決済総額を用いるか否か
     * true: 決済総額で計算
     * false: 商品合計額で計算
     * @return bool
     */
    public function getChangeableByTotal(): bool
    {
        return $this->changeableByTotal;
    }
}
