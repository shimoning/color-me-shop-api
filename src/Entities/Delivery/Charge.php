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
    public const OBJECT_FIELDS = [
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
    protected array $chargeRangesByPrice;
    protected ?int $chargeMaxPrice;

    protected array $chargeRangesByArea;
    protected array $chargeRangesByWeight;
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
     * 配送料が変わる決済金額の区分
     * [3000, 100]であれば、3000円以下の場合、手数料は100円であることを表す
     * @return array<int, int>
     */
    public function getChargeRangesByPrice(): array
    {
        $this->assertFieldInitialized('chargeRangesByPrice');
        return $this->chargeRangesByPrice;
    }

    /**
     * charge_ranges_by_priceに設定されている区分以上の金額の場合の手数料
     * @return int|null
     */
    public function getChargeMaxPrice(): ?int
    {
        return $this->chargeMaxPrice;
    }

    /**
     * 都道府県ごとの配送料
     * @return array<Area>
     */
    public function getChargeRangesByArea(): array
    {
        $this->assertFieldInitialized('chargeRangesByArea');
        return $this->chargeRangesByArea;
    }

    /**
     * 配送料が変わる重量の区分
     * @return array<Weight>
     */
    public function getChargeRangesByWeight(): array
    {
        $this->assertFieldInitialized('chargeRangesByWeight');
        return $this->chargeRangesByWeight;
    }

    /**
     * charge_ranges_by_weightに設定されている区分以上の重量の場合の手数料
     * @return array<Area>
     */
    public function getChargeRangesMaxWeight(): array
    {
        $this->assertFieldInitialized('chargeRangesMaxWeight');
        return $this->chargeRangesMaxWeight;
    }
}
