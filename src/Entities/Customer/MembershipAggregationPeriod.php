<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 会員ランク判定に使われる集計期間
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class MembershipAggregationPeriod extends Entity
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
