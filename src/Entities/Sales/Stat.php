<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 売上集計
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
 */
class Stat extends Entity
{
    protected string $accountId;
    protected int $date;
    protected int $amountToday;
    protected int $countToday;
    protected int $amountLast7days;
    protected int $countLast7days;
    protected int $amountThisMonth;
    protected int $countThisMonth;

    /**
     * ショップアカウントID
     * @return string
     */
    public function getAccountId(): string
    {
        $this->assertFieldInitialized('accountId');
        return $this->accountId;
    }

    /**
     * 集計対象とする売上の作成日
     * @return int
     */
    public function getDate(): int
    {
        $this->assertFieldInitialized('date');
        return $this->date;
    }

    /**
     * 合計売上金額
     * @return int
     */
    public function getAmountToday(): int
    {
        $this->assertFieldInitialized('amountToday');
        return $this->amountToday;
    }

    /**
     * 合計件数
     * @return int
     */
    public function getCountToday(): int
    {
        $this->assertFieldInitialized('countToday');
        return $this->countToday;
    }

    /**
     * dateを含む過去7日間の合計売上金額
     * @return int
     */
    public function getAmountLast7days(): int
    {
        $this->assertFieldInitialized('amountLast7days');
        return $this->amountLast7days;
    }

    /**
     * dateを含む過去7日間の合計件数
     * @return int
     */
    public function getCountLast7days(): int
    {
        $this->assertFieldInitialized('countLast7days');
        return $this->countLast7days;
    }

    /**
     * dateが含まれる月の合計売上金額
     * @return int
     */
    public function getAmountThisMonth(): int
    {
        $this->assertFieldInitialized('amountThisMonth');
        return $this->amountThisMonth;
    }

    /**
     * dateが含まれる月の合計件数
     * @return int
     */
    public function getCountThisMonth(): int
    {
        $this->assertFieldInitialized('countThisMonth');
        return $this->countThisMonth;
    }
}
