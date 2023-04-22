<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;

/**
 * 商品グループ
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
 */
class Group extends Entity
{
    const OBJECT_FIELDS = [
        'displayState'=> [
            'enum' => ProductDisplayState::class,
        ],
    ];

    protected int $id;
    protected string $accountId;

    protected string $name;

    protected ?string $imageUrl;
    protected ?string $expl;

    protected ?int $sort;
    protected ProductDisplayState $displayState;

    protected int $parentGroupId;

    /**
     * 商品グループID
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
     * 商品グループ名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 商品グループ画像URL
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * 商品グループ説明
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
     * @return ProductDisplayState
     */
    public function getDisplayState(): ProductDisplayState
    {
        return $this->displayState;
    }

    /**
     * 配送希望日を指定可能か
     * @return int
     */
    public function getParentGroupId(): int
    {
        return $this->parentGroupId;
    }
}
