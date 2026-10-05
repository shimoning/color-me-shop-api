<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 配送料設定の詳細
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
 */
class Charge extends Entity
{
    public const FIELD_TYPES = [
        'chargeRangesByArea' => [
            'array' => true,
            'entity' => Area::class,
        ],
        'chargeRangesMaxWeight' => [
            'array' => true,
            'entity' => Area::class,
        ],
    ];

    protected int $deliveryId;
    protected string $accountId;

    protected ?int $chargeFixed;
    /** @var list<Price> */
    protected array $chargeRangesByPrice;
    protected ?int $chargeMaxPrice;

    /** @var list<Area> */
    protected array $chargeRangesByArea;
    /** @var list<Weight> */
    protected array $chargeRangesByWeight;
    /** @var list<Area> */
    protected array $chargeRangesMaxWeight;

    /**
     * 配送料設定を生成する。
     *
     * @param array<string, mixed> $data API レスポンスデータ
     * @return void
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        if (\array_key_exists('charge_ranges_by_price', $data)) {
            $this->chargeRangesByPrice = [];
            foreach ($data['charge_ranges_by_price'] as $index => $priceCharge) {
                try {
                    if (
                        ! \is_array($priceCharge)
                        || ! \array_is_list($priceCharge)
                        || \count($priceCharge) !== 2
                        || ! \is_int($priceCharge[0])
                        || ! \is_int($priceCharge[1])
                    ) {
                        throw new \UnexpectedValueException('価格別配送料区分は2整数のタプルである必要があります。');
                    }

                    $this->chargeRangesByPrice[] = new Price([
                        'upper_limit' => $priceCharge[0],
                        'charge' => $priceCharge[1],
                    ]);
                } catch (\Throwable $error) {
                    throw InvalidFieldException::forArrayElement(
                        static::class,
                        \sprintf('charge_ranges_by_price[%s]', $index),
                        Price::class,
                        $error,
                    );
                }
            }
        }

        if (! \array_key_exists('charge_ranges_by_weight', $data)) {
            return;
        }

        $this->chargeRangesByWeight = [];
        foreach ($data['charge_ranges_by_weight'] as $index => $weight) {
            try {
                if (
                    ! \is_array($weight)
                    || ! \array_key_exists(0, $weight)
                    || ! \array_key_exists(1, $weight)
                ) {
                    throw new \UnexpectedValueException('重量別配送料の行形式が不正です。');
                }

                $this->chargeRangesByWeight[] = new Weight([
                    'weight' => $weight[0],
                    'areas' => $weight[1],
                ]);
            } catch (\Throwable $error) {
                throw InvalidFieldException::forArrayElement(
                    static::class,
                    \sprintf('charge_ranges_by_weight[%s]', $index),
                    Weight::class,
                    $error,
                );
            }
        }
    }

    /**
     * 配送方法ID
     * @return int
     */
    public function getDeliveryId(): int
    {
        $this->assertFieldInitialized('deliveryId');
        return $this->deliveryId;
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
     * 配送料が固定の場合の金額
     * @return int|null
     */
    public function getChargeFixed(): ?int
    {
        return $this->chargeFixed;
    }

    /**
     * 注文金額ごとの配送料の区分。
     *
     * 公式 OpenAPI との差分: 区分の上限を「以下」と説明しているが、実 API の設定では上限はその区分に
     * 含まれない (未満) (2026-10-05)。
     *
     * @return list<Price>
     * @see docs/api-delivery-charge-observation.md
     */
    public function getChargeRangesByPrice(): array
    {
        $this->assertFieldInitialized('chargeRangesByPrice');
        return $this->chargeRangesByPrice;
    }

    /**
     * 最後の区分の上限以上の注文金額に対する配送料。金額別の配送料を設定していない場合は null。
     *
     * @return int|null
     * @see docs/api-delivery-charge-observation.md
     */
    public function getChargeMaxPrice(): ?int
    {
        return $this->chargeMaxPrice;
    }

    /**
     * 都道府県ごとの配送料
     * @return list<Area>
     */
    public function getChargeRangesByArea(): array
    {
        $this->assertFieldInitialized('chargeRangesByArea');
        return $this->chargeRangesByArea;
    }

    /**
     * 配送料が変わる重量の区分
     * @return list<Weight>
     */
    public function getChargeRangesByWeight(): array
    {
        $this->assertFieldInitialized('chargeRangesByWeight');
        return $this->chargeRangesByWeight;
    }

    /**
     * charge_ranges_by_weightに設定されている区分以上の重量の場合の手数料
     * @return list<Area>
     */
    public function getChargeRangesMaxWeight(): array
    {
        $this->assertFieldInitialized('chargeRangesMaxWeight');
        return $this->chargeRangesMaxWeight;
    }
}
