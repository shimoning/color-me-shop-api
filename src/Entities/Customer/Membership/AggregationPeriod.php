<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer\Membership;

use Shimoning\ColorMeShopApi\Aliases;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 会員ランク判定に使われる集計期間
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class AggregationPeriod extends Entity
{
    protected int $startDate;
    protected int $endDate;

    /**
     * 集計期間の開始日時
     * @return DateTimeImmutable
     */
    public function getStartDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('startDate');
        return (new DateTimeImmutable)->setTimestamp($this->startDate);
    }

    /**
     * 集計期間の終了日時
     * @return DateTimeImmutable
     */
    public function getEndDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('endDate');
        return (new DateTimeImmutable)->setTimestamp($this->endDate);
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(AggregationPeriod::class);
