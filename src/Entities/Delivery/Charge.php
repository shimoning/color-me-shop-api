<?php

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\DisplayState;

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
        'displayState' => [
            'enum' => DisplayState::class,
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

    public function __construct(array $data)
    {
        parent::__construct($data);

        // chargeRangesByWeight
        $this->chargeRangesByWeight = [];
        foreach ($data['charge_ranges_by_weight'] ?? [] as $weight) {
            $this->chargeRangesByWeight[] = new Weight([
                'weight' => $weight[0],
                'areas' => $weight[1],
            ]);
        }
    }

    /**
     * 決済方法ID
     * @return int
     */
    public function getDeliveryId(): int
    {
        return $this->deliveryId;
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
        return $this->chargeRangesByArea;
    }

    /**
     * 配送料が変わる重量の区分
     * @return array<Weight>
     */
    public function getChargeRangesByWeight(): array
    {
        return $this->chargeRangesByWeight;
    }

    /**
     * charge_ranges_by_weightに設定されている区分以上の重量の場合の手数料
     * @return array<Area>
     */
    public function getChargeRangesMaxWeight(): array
    {
        return $this->chargeRangesMaxWeight;
    }
}
