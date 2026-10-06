<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer\Membership;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 次の会員ランクへの進捗情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class Progress extends Entity
{
    const FIELD_TYPES = [
        'aggregationPeriod' => [
            'entity' => AggregationPeriod::class,
        ],
        'nextMembership' => [
            'nullable' => true,
            'entity' => NextMembership::class,
        ],
    ];

    protected int $score;
    protected AggregationPeriod $aggregationPeriod;
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
     * @return AggregationPeriod
     */
    public function getAggregationPeriod(): AggregationPeriod
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Progress::class);
