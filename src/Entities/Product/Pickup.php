<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品内 pickups[] と、ピックアップ書き込み API (POST / PUT / DELETE) の `pickup` 応答。
 * OpenAPI の productPickup スキーマにある product_id / account_id は、商品内の実応答にはなく、
 * 書き込み応答の6キーには含まれるため nullable として宣言する。
 * 出典: docs/api-product-structure.md (fa4bfbb、書き込み観測 b1ceab5)。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Pickup extends Entity
{
    protected int $pickupType;
    protected ?int $productId;
    protected ?string $accountId;
    protected ?int $orderNum;
    protected int $makeDate;
    protected int $updateDate;

    /**
     * 商品内 pickups[] は OpenAPI の productPickup と異なり product_id/account_id を含まない (docs/api-product-structure.md, fa4bfbb)。
     * @return int
     */
    public function getPickupType(): int
    {
        $this->assertFieldInitialized('pickupType');
        return $this->pickupType;
    }

    /**
     * 商品ID。商品内 pickups[] の応答にはなく、書き込み応答にだけ含まれる。
     * @return ?int
     */
    public function getProductId(): ?int
    {
        return $this->productId;
    }

    /**
     * ショップアカウントID。商品内 pickups[] の応答にはなく、書き込み応答にだけ含まれる。
     * @return ?string
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * 商品の表示順
     * @return ?int
     */
    public function getOrderNum(): ?int
    {
        return $this->orderNum;
    }

    /**
     * 作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    /**
     * 更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }
}
