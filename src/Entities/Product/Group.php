<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

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
        'displayState' => [
            'enum' => ProductDisplayState::class,
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
    protected ProductDisplayState $displayState;

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
     * @return ProductDisplayState
     */
    public function getDisplayState(): ProductDisplayState
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
     * 実 API の観測 (2026-09-21) では、グループの `meta_tag` は初回設定 (null から値へ) だけが永続化され、
     * 以後の PUT は応答には反映されるが GET では初回設定の値のままだった (API 側の挙動と考えられ、未解決)。
     * 更新直後の応答の値と、後から取得した値が一致しない場合がある。出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
     *
     * @return MetaTag|null
     */
    public function getMetaTag(): ?MetaTag
    {
        return $this->metaTag ?? null;
    }
}
