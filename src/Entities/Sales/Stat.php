<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 売上集計
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
 */
class Stat extends Entity
{
    /**
     * 数字の前にアンダースコアが入るため、自動変換では公式のフィールド名に戻せない。
     */
    const FIELD_NAMES = [
        'amountLast7days' => 'amount_last_7days',
        'countLast7days' => 'count_last_7days',
    ];

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
     * 集計の基準日
     * Services\Sales::stat() に渡した日付の 00:00（JST）を返す。
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('date');
        return (new DateTimeImmutable)->setTimestamp($this->date);
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
