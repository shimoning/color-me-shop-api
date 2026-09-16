<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 次の会員ランクへの進捗情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class MembershipProgress extends Entity
{
    const OBJECT_FIELDS = [
        'aggregationPeriod' => [
            'entity' => MembershipAggregationPeriod::class,
        ],
        'nextMembership' => [
            'nullable' => true,
            'entity' => NextMembership::class,
        ],
    ];

    protected int $score;
    protected MembershipAggregationPeriod $aggregationPeriod;
    protected ?NextMembership $nextMembership;

    /**
     * 集計期間中の購入金額（税込）
     * @return int
     */
    public function getScore(): int
    {
        $this->assertFieldInitialized('score');
        return $this->score;
    }

    /**
     * ランク判定に使われる集計期間
     * @return MembershipAggregationPeriod
     */
    public function getAggregationPeriod(): MembershipAggregationPeriod
    {
        $this->assertFieldInitialized('aggregationPeriod');
        return $this->aggregationPeriod;
    }

    /**
     * 次に目指す会員ランク
     * @return NextMembership|null
     */
    public function getNextMembership(): ?NextMembership
    {
        return $this->nextMembership ?? null;
    }
}
