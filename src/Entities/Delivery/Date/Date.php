<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Delivery\Date;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 配送日時設定。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveryDateSetting
 */
class Date extends Entity
{
    public const FIELD_TYPES = [
        'days' => ['entity' => Days::class],
        'times' => ['entity' => Times::class],
    ];

    protected string $accountId;
    protected Days $days;
    protected Times $times;
    protected ?int $makeDate;
    protected ?int $updateDate;

    /**
     * ショップアカウントID。
     */
    public function getAccountId(): string
    {
        $this->assertFieldInitialized('accountId');
        return $this->accountId;
    }

    /**
     * 配送希望日の設定。
     */
    public function getDays(): Days
    {
        $this->assertFieldInitialized('days');
        return $this->days;
    }

    /**
     * 配送時間帯の設定。
     */
    public function getTimes(): Times
    {
        $this->assertFieldInitialized('times');
        return $this->times;
    }

    /**
     * 配送日時設定作成日時。
     */
    public function getMakeDate(): ?DateTimeImmutable
    {
        if ($this->makeDate === null) {
            return null;
        }
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    /**
     * 配送日時設定更新日時。
     */
    public function getUpdateDate(): ?DateTimeImmutable
    {
        if ($this->updateDate === null) {
            return null;
        }
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(Date::class);
