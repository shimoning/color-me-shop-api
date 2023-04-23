<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;

/**
 * 商品カテゴリー
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
 */
class Category extends Entity
{
    const OBJECT_FIELDS = [
        'displayState' => [
            'enum' => CategoryDisplayState::class,
        ],
        'children' => [
            'array' => true,
            'entity' => Category::class,
        ],
    ];

    protected int $idBig;
    protected int $idSmall;
    protected string $accountId;

    protected string $name;

    protected ?string $imageUrl;
    protected ?string $expl;

    protected ?int $sort;
    protected CategoryDisplayState $displayState;

    protected int $makeDate;
    protected int $updateDate;

    protected array $children;

    /**
     * 大カテゴリーID
     * @return int
     */
    public function getIdBig(): int
    {
        return $this->id;
    }
    /**
     * 小カテゴリーID。大カテゴリーのことを表している場合は0
     * @return int
     */
    public function getIdSmall(): int
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
     * 商品カテゴリー名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 商品カテゴリー画像URL
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * 商品カテゴリー説明
     * @return string|null
     */
    public function getExpl(): ?string
    {
        return $this->expl;
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
     * @return CategoryDisplayState
     */
    public function getDisplayState(): CategoryDisplayState
    {
        return $this->displayState;
    }

    /**
     * 商品カテゴリー作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * 商品カテゴリー更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->updateDate);
    }

    /**
     * 子カテゴリー
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
