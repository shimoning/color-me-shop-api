<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\PaymentType;

/**
 * 決済設定
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 */
class Payment extends Entity
{
    const OBJECT_FIELDS = [
        'type' => [
            'enum' => PaymentType::class,
        ],
        'cod' => [
            'entity' => Cod::class,
        ],
        'card' => [
            'entity' => Card::class,
        ],
        'financial' => [
            'entity' => Financial::class,
        ],
    ];

    protected int $id;
    protected string $accountId;

    protected string $name;
    protected ?int $fee;

    protected ?string $ipCode;

    protected ?string $memo;
    protected ?string $orderEndNote;
    protected ?string $memoMobile;

    protected ?int $sort;
    protected ?string $imageUrl;

    protected PaymentType $type;

    protected bool $display;
    protected bool $useMobile;

    protected int $makeDate;
    protected int $updateDate;

    protected ?Cod $cod;
    protected ?Card $card;
    protected ?Financial $financial;

    /**
     * 決済方法ID
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
     * 決済名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 決済手数料
     * @return int|null
     */
    public function getFee(): ?int
    {
        return $this->fee;
    }

    /**
     * GMOイプシロン等との契約コード
     * @return string|null
     */
    public function getIpCode(): ?string
    {
        return $this->ipCode;
    }

    /**
     * 説明
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * 説明
     * @return string|null
     */
    public function getOrderEndNote(): ?string
    {
        return $this->orderEndNote;
    }

    /**
     * フィーチャーフォン向けショップ用の説明
     * @return string|null
     */
    public function getMemoMobile(): ?string
    {
        return $this->memoMobile;
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
     * 決済画像URL
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * 決済タイプ
     * @return PaymentType
     */
    public function getType(): PaymentType
    {
        return $this->type;
    }

    /**
     * 表示設定。trueなら表示される
     * @return bool
     */
    public function getDisplay(): bool
    {
        return $this->display;
    }

    /**
     * フィーチャーフォン向けショップでの表示設定
     * @return bool
     */
    public function getUseMobile(): bool
    {
        return $this->useMobile;
    }

    /**
     * 決済作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * 決済更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->updateDate);
    }

    /**
     * 代引き決済の設定情報。代引き決済の場合のみ存在する
     * @return Cod|null
     */
    public function getCod(): ?Cod
    {
        return $this->cod;
    }

    /**
     * クレジットカードの設定情報。クレジットカード決済の場合のみ存在する
     * @return Card|null
     */
    public function getCard(): ?Card
    {
        return $this->card;
    }

    /**
     * 代引き決済の設定情報。代引き決済の場合のみ存在する
     * @return Financial|null
     */
    public function getFinancial(): ?Financial
    {
        return $this->financial;
    }
}
