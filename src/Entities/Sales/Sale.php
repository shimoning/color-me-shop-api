<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

/**
 * 受注データ
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class Sale extends Entity
{
    const OBJECT_FIELDS = [
        'customer' => Customer::class,
        'details' => [
            'array' => true,
            'entity' => SaleDetail::class,
        ],
        'saleDeliveries' => [
            'array' => true,
            'entity' => SaleDelivery::class,
        ],
        'acceptedMailState' => [
            'enum' => MailState::class,
        ],
        'paidMailState' => [
            'enum' => MailState::class,
        ],
        'deliveredMailState' => [
            'enum' => MailState::class,
        ],
        'pointState' => [
            'enum' => PointState::class,
        ],
        'gmoPointState' => [
            'enum' => PointState::class,
        ],
        'yahooPointState' => [
            'enum' => PointState::class,
        ],
    ];

    protected int $id;
    protected string $accountId;

    protected int $makeDate;
    protected int $updateDate;

    protected ?string $memo;

    protected int $paymentId;

    protected bool $mobile;
    protected bool $paid;
    protected bool $delivered;
    protected bool $canceled;

    protected MailState $acceptedMailState;
    protected MailState $paidMailState;
    protected MailState $deliveredMailState;

    protected ?int $acceptedMailSentDate;
    protected ?int $paidMailSentDate;
    protected ?int $deliveredMailSentDate;

    protected PointState $pointState;
    protected ?PointState $gmoPointState;
    protected ?PointState $yahooPointState;

    protected int $productTotalPrice;
    protected int $deliveryTotalCharge;
    protected int $fee;
    protected int $tax;
    protected int $noshiTotalCharge;
    protected int $cardTotalCharge;
    protected int $wrappingTotalCharge;

    protected int $pointDiscount;
    protected int $gmoPointDiscount;
    protected int $otherDiscount;
    protected string $otherDiscountName;

    protected int $totalPrice;

    protected int $grantedPoints;
    protected int $usePoints;
    protected int $grantedGmoPoints;
    protected int $useGmoPoints;
    protected int $grantedYahooPoints;
    protected int $useYahooPoints;

    protected string $externalOrderId;

    protected $customer;
    protected array $details;
    protected array $saleDeliveries;

    /**
     * 売上ID
     * @return int
     */
    public function getId(): int
    {
        $this->assertFieldInitialized('id');
        return $this->id;
    }

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
     * 受注日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * 受注更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable)->setTimestamp($this->updateDate);
    }

    /**
     * (削除) 備考
     *
     * https://developer.shop-pro.jp/news/releases/deprecate_v1_sales_memo_20260616
     * @deprecated 0.7.0
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo ?? null;
    }

    /**
     * 使用された決済方法ID
     * @return int
     */
    public function getPaymentId(): int
    {
        $this->assertFieldInitialized('paymentId');
        return $this->paymentId;
    }

    /**
     * モバイルからの注文であるか否か
     * @return bool
     */
    public function isMobile(): bool
    {
        $this->assertFieldInitialized('mobile');
        return $this->mobile;
    }

    /**
     * 入金済みであるか否か
     * @return bool
     */
    public function isPaid(): bool
    {
        $this->assertFieldInitialized('paid');
        return $this->paid;
    }

    /**
     * 発送済みである否か
     * @return bool
     */
    public function isDelivered(): bool
    {
        $this->assertFieldInitialized('delivered');
        return $this->delivered;
    }

    /**
     * キャンセル済みであるか否か
     * @return bool
     */
    public function isCanceled(): bool
    {
        $this->assertFieldInitialized('canceled');
        return $this->canceled;
    }

    /**
     * 受注メールの送信状態
     * @return MailState
     */
    public function getAcceptedMailState(): MailState
    {
        $this->assertFieldInitialized('acceptedMailState');
        return $this->acceptedMailState;
    }

    /**
     * 入金メールの送信状態
     * @return MailState
     */
    public function getPaidMailState(): MailState
    {
        $this->assertFieldInitialized('paidMailState');
        return $this->paidMailState;
    }

    /**
     * 発送メールの送信状態
     * @return MailState
     */
    public function getDeliveredMailState(): MailState
    {
        $this->assertFieldInitialized('deliveredMailState');
        return $this->deliveredMailState;
    }

    /**
     * 受注メールの送信日時
     * @return DateTimeImmutable|null
     */
    public function getAcceptedMailSentDate(): ?DateTimeImmutable
    {
        return $this->acceptedMailSentDate
            ? (new DateTimeImmutable)->setTimestamp($this->acceptedMailSentDate)
            : null;
    }

    /**
     * 入金メールの送信日時
     * @return DateTimeImmutable|null
     */
    public function getPaidMailSentDate(): ?DateTimeImmutable
    {
        return $this->paidMailSentDate
            ? (new DateTimeImmutable)->setTimestamp($this->paidMailSentDate)
            : null;
    }

    /**
     * 発送メールの送信日時
     * @return DateTimeImmutable|null
     */
    public function getDeliveredMailSentDate(): ?DateTimeImmutable
    {
        return $this->deliveredMailSentDate
            ? (new DateTimeImmutable)->setTimestamp($this->deliveredMailSentDate)
            : null;
    }

    /**
     * ショップポイント付与状態
     * @return PointState
     */
    public function getPointState(): PointState
    {
        $this->assertFieldInitialized('pointState');
        return $this->pointState;
    }

    /**
     * GMOポイント付与状態
     * @return PointState|null
     */
    public function getGmoPointState(): ?PointState
    {
        return $this->gmoPointState;
    }

    /**
     * Yahooポイント付与状態
     * @return PointState|null
     */
    public function getYahooPointState(): ?PointState
    {
        return $this->yahooPointState;
    }

    /**
     * 商品の合計金額
     * @return int
     */
    public function getProductTotalPrice(): int
    {
        $this->assertFieldInitialized('productTotalPrice');
        return $this->productTotalPrice;
    }

    /**
     * 配送料
     * @return int
     */
    public function getDeliveryTotalCharge(): int
    {
        $this->assertFieldInitialized('deliveryTotalCharge');
        return $this->deliveryTotalCharge;
    }

    /**
     * 決済手数料
     * @return int
     */
    public function getFee(): int
    {
        $this->assertFieldInitialized('fee');
        return $this->fee;
    }

    /**
     * 商品合計金額に対する消費税
     * @return int
     */
    public function getTax(): int
    {
        $this->assertFieldInitialized('tax');
        return $this->tax;
    }

    /**
     * 熨斗料金
     * @return int
     */
    public function getNoshiTotalCharge(): int
    {
        $this->assertFieldInitialized('noshiTotalCharge');
        return $this->noshiTotalCharge;
    }

    /**
     * メッセージカード料金
     * @return int
     */
    public function getCardTotalCharge(): int
    {
        $this->assertFieldInitialized('cardTotalCharge');
        return $this->cardTotalCharge;
    }

    /**
     * ラッピング料金
     * @return int
     */
    public function getWrappingTotalCharge(): int
    {
        $this->assertFieldInitialized('wrappingTotalCharge');
        return $this->wrappingTotalCharge;
    }

    /**
     * ショップポイントによる割引額
     * @return int
     */
    public function getPointDiscount(): int
    {
        $this->assertFieldInitialized('pointDiscount');
        return $this->pointDiscount;
    }

    /**
     * GMOポイントによる割引額
     * @return int
     */
    public function getGmoPointDiscount(): int
    {
        $this->assertFieldInitialized('gmoPointDiscount');
        return $this->gmoPointDiscount;
    }

    /**
     * その他、クーポン等による割引額
     * @return int
     */
    public function getOtherDiscount(): int
    {
        $this->assertFieldInitialized('otherDiscount');
        return $this->otherDiscount;
    }

    /**
     * その他割引の名称
     * @return string
     */
    public function getOtherDiscountName(): string
    {
        $this->assertFieldInitialized('otherDiscountName');
        return $this->otherDiscountName;
    }

    /**
     * 注文総額
     * @return int
     */
    public function getTotalPrice(): int
    {
        $this->assertFieldInitialized('totalPrice');
        return $this->totalPrice;
    }

    /**
     * 付与されたショップポイント数
     * @return int
     */
    public function getGrantedPoints(): int
    {
        $this->assertFieldInitialized('grantedPoints');
        return $this->grantedPoints;
    }

    /**
     * 使用されたショップポイント数
     * @return int
     */
    public function getUsePoints(): int
    {
        $this->assertFieldInitialized('usePoints');
        return $this->usePoints;
    }

    /**
     * 付与されたGMOポイント数
     * @return int
     */
    public function getGrantedGmoPoints(): int
    {
        $this->assertFieldInitialized('grantedGmoPoints');
        return $this->grantedGmoPoints;
    }

    /**
     * 使用されたGMOポイント数
     * @return int
     */
    public function getUseGmoPoints(): int
    {
        $this->assertFieldInitialized('useGmoPoints');
        return $this->useGmoPoints;
    }

    /**
     * 付与されたYahooポイント数
     * @return int
     */
    public function getGrantedYahooPoints(): int
    {
        $this->assertFieldInitialized('grantedYahooPoints');
        return $this->grantedYahooPoints;
    }

    /**
     * 使用されたYahooポイント数
     * @return int
     */
    public function getUseYahooPoints(): int
    {
        $this->assertFieldInitialized('useYahooPoints');
        return $this->useYahooPoints;
    }

    /**
     * 外部システムで発行された決済識別番号
     * 該当受注の決済が以下のいずれかである場合、その決済の決済識別番号を返します。
     *  - 楽天ペイ（オンライン決済）
     *  - LINE Pay
     *  - PayPal
     *  - Commerce Platform
     *  - Amazon Pay
     *  - Amazon Pay V2
     *  - Square対面決済
     * それ以外の決済に関しては空文字列を返します。
     * @return string
     */
    public function getExternalOrderId(): string
    {
        $this->assertFieldInitialized('externalOrderId');
        return $this->externalOrderId;
    }

    /**
     * 顧客
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        if ($this->customer === null) {
            throw MissingFieldException::for(static::class, static::apiFieldName('customer'));
        }

        return $this->customer;
    }

    /**
     * 受注明細
     * @return array<SaleDetail>
     */
    public function getDetails(): array
    {
        $this->assertFieldInitialized('details');
        return $this->details;
    }

    /**
     * お届け先
     * @return array<SaleDelivery>
     */
    public function getSaleDeliveries(): array
    {
        $this->assertFieldInitialized('saleDeliveries');
        return $this->saleDeliveries;
    }
}
