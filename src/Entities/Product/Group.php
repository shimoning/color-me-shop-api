<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;

/**
 * 商品グループ
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
 */
class Group extends Entity
{
    const FIELD_TYPES = [
        'displayState' => [
            'enum' => GroupDisplayState::class,
        ],
        'metaTag' => [
            'allowNull' => true,
            'entity' => MetaTag::class,
        ],
    ];

    protected int $id;
    protected string $accountId;

    protected string $name;

    protected ?string $imageUrl;
    protected ?string $expl;

    protected ?int $sort;
    protected GroupDisplayState $displayState;

    protected ?int $parentGroupId;
    protected ?MetaTag $metaTag;

    /**
     * 商品グループID
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
     * 商品グループ名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');

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
     *
     * 公式 OpenAPI との差分: 応答定義にない `members_only` も実 API が返す (2026-09-22)。
     * 応答定義にだけある2値も受理できる `GroupDisplayState` を返す。
     *
     * @return GroupDisplayState
     * @see docs/api-product-structure.md
     */
    public function getDisplayState(): GroupDisplayState
    {
        $this->assertFieldInitialized('displayState');

        return $this->displayState;
    }

    /**
     * 親の商品グループID
     *
     * 親グループが存在しない場合は null になる。
     *
     * @return int|null
     */
    public function getParentGroupId(): ?int
    {
        return $this->parentGroupId;
    }

    /**
     * SEOメタタグ情報
     *
     * meta_tag が欠損または null の場合は null、空オブジェクトの場合は MetaTag を返す。
     *
     * 更新直後の応答と後続の GET で値が異なる場合がある (2026-09-21)。
     *
     * @return MetaTag|null
     * @see docs/api-product-structure.md
     */
    public function getMetaTag(): ?MetaTag
    {
        return $this->metaTag ?? null;
    }
}
