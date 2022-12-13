<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\Prefecture;

/**
 * お届け先
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class SaleDelivery extends Entity
{
    protected int $id;
    protected int $saleId;
    protected string $accountId;
    protected int $deliveryId;
    protected array $detailIds;

    protected string $name;
    protected string $furigana;

    protected string $postal;
    protected int $prefId;
    protected string $perfName;
    protected ?string $address1;
    protected ?string $address2;
    protected ?string $tel;

    protected ?string $preferredDate;
    protected ?string $preferredPeriod;
    protected ?string $slipNumber;

    protected ?string $noshiText;
    protected ?int $noshiCharge;

    protected ?string $cardName;
    protected ?string $cardText;
    protected ?int $cardCharge;

    protected ?string $wrappingName;
    protected ?int $wrappingCharge;

    protected int $deliveryCharge;
    protected int $totalCharge;

    protected ?string $trackingUrl;
    protected ?string $memo;
    protected bool $delivered;

    /**
     * お届け先ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 売上ID
     * @return int
     */
    public function getSaleId(): int
    {
        return $this->saleId;
    }

    /**
     * ショップアカウントID
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * 使用された配送方法ID
     * @return int
     */
    public function getDeliveryId(): int
    {
        return $this->deliveryId;
    }

    /**
     * この配送に含まれる受注明細IDの配列
     * @return string[]
     */
    public function getDetailIds(): array
    {
        return $this->detailIds;
    }

    /**
     * 宛名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 宛名のフリガナ
     * @return string
     */
    public function getFurigana(): string
    {
        return $this->furigana;
    }

    /**
     * 郵便番号
     * @return string
     */
    public function getPostal(): string
    {
        return $this->postal;
    }

    /**
     * 都道府県の通し番号
     * @return Prefecture|int
     */
    public function getPrefId(): mixed
    {
        return Prefecture::tryFrom($this->prefId) ?? $this->prefId;
    }

    /**
     * 都道府県名
     * @return string
     */
    public function getPrefName(): string
    {
        return $this->perfName;
    }

    /**
     * 住所1
     * @return string|null
     */
    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    /**
     * 住所2
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    /**
     * 電話番号
     * @return string|null
     */
    public function getTel(): ?string
    {
        return $this->tel;
    }

    /**
     * 配送希望日
     * @return string |null
     */
    public function getPreferredDate(): ?string
    {
        return $this->preferredDate;
    }

    /**
     * 配送希望時間帯
     * @return string|null
     */
    public function getPreferredPeriod(): ?string
    {
        return $this->preferredPeriod;
    }

    /**
     * 配送伝票番号
     * @return string|null
     */
    public function getSlipNumber(): ?string
    {
        return $this->slipNumber;
    }

    /**
     * 熨斗の文言
     * @return string|null
     */
    public function getNoshiText(): ?string
    {
        return $this->noshiText;
    }

    /**
     * 熨斗の料金
     * @return int|null
     */
    public function getNoshiCharge(): ?int
    {
        return $this->noshiCharge;
    }

    /**
     * メッセージカードの表示名
     * @return string|null
     */
    public function getCardName(): ?string
    {
        return $this->cardName;
    }

    /**
     * メッセージカードのテキスト
     * @return string|null
     */
    public function getCardText(): ?string
    {
        return $this->cardText;
    }

    /**
     * メッセージカードの料金
     * @return int|null
     */
    public function getCardCharge(): ?int
    {
        return $this->cardCharge;
    }

    /**
     * ラッピングの表示名
     * @return string|null
     */
    public function getWrappingName(): ?string
    {
        return $this->wrappingName;
    }

    /**
     * ラッピングの料金
     * @return int|null
     */
    public function getWrappingCharge(): ?int
    {
        return $this->wrappingCharge;
    }

    /**
     * 配送料
     * @return int
     */
    public function getDeliveryCharge(): int
    {
        return $this->deliveryCharge;
    }

    /**
     * 配送料・手数料の小計
     * @return int
     */
    public function getTotalCharge(): int
    {
        return $this->totalCharge;
    }

    /**
     * 配送状況確認URL
     * @return string|null
     */
    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    /**
     * 備考
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * 発送済みであるか否か
     * @return bool
     */
    public function isDelivered(): bool
    {
        return $this->delivered;
    }
}
