<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品内 pickups[] と、ピックアップ書き込み API (POST / PUT / DELETE) の `pickup` 応答。
 *
 * 公式 OpenAPI との差分: productPickup の `product_id` / `account_id` は商品内の応答にはなく、
 * 書き込み応答にだけ含まれるため nullable とする (2026-09-18、2026-09-20)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
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
     * ピックアップ種別
     *
     * 公式 OpenAPI との差分: 商品内 `pickups[]` は `product_id` / `account_id` を含まない (2026-09-18)。
     *
     * @return int
     * @see docs/api-product-structure.md
     */
    public function getPickupType(): int
    {
        $this->assertFieldInitialized('pickupType');
        return $this->pickupType;
    }

    /**
     * 商品ID。商品内 pickups[] の応答にはなく、書き込み応答にだけ含まれる。
     * フィールド追加前に serialize された Pickup では未初期化のため null を返す。
     * @return ?int
     */
    public function getProductId(): ?int
    {
        return $this->productId ?? null;
    }

    /**
     * ショップアカウントID。商品内 pickups[] の応答にはなく、書き込み応答にだけ含まれる。
     * フィールド追加前に serialize された Pickup では未初期化のため null を返す。
     * @return ?string
     */
    public function getAccountId(): ?string
    {
        return $this->accountId ?? null;
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
