<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\DeliveryMethodType;
use Shimoning\ColorMeShopApi\Constants\DeliveryChargeFreeType;
use Shimoning\ColorMeShopApi\Constants\DeliveryChargeType;
use Shimoning\ColorMeShopApi\Constants\DisplayState;

/**
 * 配送方法
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
 */
class Delivery extends Entity
{
    const OBJECT_FIELDS = [
        'methodType'=> [
            'enum' => DeliveryMethodType::class,
        ],
        'chargeFreeType'=> [
            'enum' => DeliveryChargeFreeType::class,
        ],
        'chargeType'=> [
            'enum' => DeliveryChargeType::class,
        ],
        'displayState'=> [
            'enum' => DisplayState::class,
        ],
        'charge'=> [
            'entity' => Charge::class,
        ],
    ];

    protected int $id;
    protected string $accountId;

    protected string $name;

    protected DeliveryMethodType $methodType;
    protected ?string $imageUrl;

    protected DeliveryChargeFreeType $chargeFreeType;
    protected ?int $chargeFreeLimit;
    protected DeliveryChargeType $chargeType;
    protected Charge $charge;
    protected bool $taxIncluded;

    protected bool $slipNumberUse;
    protected ?string $slipNumberUrl;

    protected ?string $memo;
    protected ?string $memo2;

    protected ?int $sort;
    protected DisplayState $displayState;

    protected bool $preferredDateUse;
    protected bool $preferredPeriodUse;

    protected array $unavailablePaymentIds;

    protected int $makeDate;
    protected int $updateDate;

    /**
     * 配送方法ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * 配送方法名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 配送方法区分
     * @return DeliveryMethodType
     */
    public function getMethodType(): DeliveryMethodType
    {
        return $this->methodType;
    }

    /**
     * 配送方法画像URL
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * 配送料が無料になる基準
     * @return DeliveryChargeFreeType
     */
    public function getChargeFreeType(): DeliveryChargeFreeType
    {
        return $this->chargeFreeType;
    }

    /**
     * 配送料が無料になる金額
     * @return int|null
     */
    public function getChargeFreeLimit(): ?int
    {
        return $this->chargeFreeLimit;
    }

    /**
     * フィーチャーフォン向けショップ用の説明
     * @return sDeliveryChargeType
     */
    public function getChargeType(): DeliveryChargeType
    {
        return $this->chargeType;
    }

    /**
     * 配送料設定の詳細
     * @return Charge
     */
    public function getCharge(): Charge
    {
        return $this->charge;
    }

    /**
     * 送料が税込み料金であるか否か
     * @return bool
     */
    public function getTaxIncluded(): bool
    {
        return $this->taxIncluded;
    }

    /**
     * 配送伝票番号設定を使用するか否か
     * @return bool
     */
    public function getSlipNumberUse(): bool
    {
        return $this->slipNumberUse;
    }

    /**
     * 配送伝票番号確認URL
     * @return string|null
     */
    public function getSlipNumberUrl(): ?string
    {
        return $this->slipNumberUrl;
    }

    /**
     * 配送方法の説明
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * フィーチャーフォン向けショップ用の配送方法説明
     * @return string|null
     */
    public function getMemo2(): ?string
    {
        return $this->memo2;
    }

    /**
     * 表示順
     * @return int|null
     */
    public function getSort(): ?int
    {
        return $this->sort;
    }

    /**
     * 表示状態
     * @return DisplayState
     */
    public function getDisplayState(): DisplayState
    {
        return $this->displayState;
    }


    /**
     * 配送希望日を指定可能か
     * @return bool
     */
    public function getPreferredDateUse(): bool
    {
        return $this->preferredDateUse;
    }

    /**
     * 配送時間帯を指定可能か
     * @return bool
     */
    public function getPreferredPeriodUse(): bool
    {
        return $this->preferredPeriodUse;
    }

    /**
     * 利用不可決済方法の配列
     * @return array<int>
     */
    public function getUnavailablePaymentIds(): array
    {
        return $this->unavailablePaymentIds;
    }

    /**
     * 配送方法作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * 配送方法更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->updateDate);
    }
}
