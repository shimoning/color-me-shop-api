<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * ギフト設定。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/gift/operation/getGift
 */
class Gift extends Entity
{
    public const OBJECT_FIELDS = [
        'noshi' => ['entity' => GiftNoshi::class],
        'card' => ['entity' => GiftCard::class],
        'wrapping' => ['entity' => GiftWrapping::class],
    ];

    protected string $accountId;
    protected ?bool $enabled;
    protected GiftNoshi $noshi;
    protected GiftCard $card;
    protected GiftWrapping $wrapping;
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
     * ギフト設定が有効であるか。
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * のし設定。
     */
    public function getNoshi(): GiftNoshi
    {
        $this->assertFieldInitialized('noshi');
        return $this->noshi;
    }

    /**
     * メッセージカード設定。
     */
    public function getCard(): GiftCard
    {
        $this->assertFieldInitialized('card');
        return $this->card;
    }

    /**
     * ラッピング設定。
     */
    public function getWrapping(): GiftWrapping
    {
        $this->assertFieldInitialized('wrapping');
        return $this->wrapping;
    }

    /**
     * ギフト設定作成日時。
     */
    public function getMakeDate(): ?DateTimeImmutable
    {
        if ($this->makeDate === null) {
            return null;
        }
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    /**
     * ギフト設定更新日時。
     */
    public function getUpdateDate(): ?DateTimeImmutable
    {
        if ($this->updateDate === null) {
            return null;
        }
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }
}
