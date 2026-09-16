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
        'metaTag' => [
            'entity' => MetaTag::class,
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
    protected ?MetaTag $metaTag;

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
        $this->assertFieldInitialized('idBig');
        return $this->idBig;
    }
    /**
     * 小カテゴリーID。大カテゴリーのことを表している場合は0
     * @return int
     */
    public function getIdSmall(): int
    {
        $this->assertFieldInitialized('idSmall');
        return $this->idSmall;
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
     * 商品カテゴリー名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
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
     * 商品カテゴリーのメタタグ
     *
     * 公式 OpenAPI（2026-09-16 確認）の
     * components.schemas.productCategory.properties.meta_tag.allOf[0].properties と
     * components.schemas.productCategoryChild.properties.meta_tag.allOf[0].properties で、
     * title / keywords / description が nullable として定義されている。
     * 2026-09-12 の実 API 検証では、親カテゴリー2件中1件で meta_tag 自体の欠損を確認したため、
     * API レスポンスに含まれない場合は null を返す。
     *
     * @return MetaTag|null
     */
    public function getMetaTag(): ?MetaTag
    {
        return $this->metaTag;
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
        $this->assertFieldInitialized('displayState');
        return $this->displayState;
    }

    /**
     * 商品カテゴリー作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * 商品カテゴリー更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable)->setTimestamp($this->updateDate);
    }

    /**
     * 子カテゴリー
     * @return Category[]
     */
    public function getChildren(): array
    {
        $this->assertFieldInitialized('children');
        return $this->children;
    }

    /**
     * SEOメタタグ情報
     * @return MetaTag
     */
    public function getMetaTag(): MetaTag
    {
        $this->assertFieldInitialized('metaTag');
        return $this->metaTag;
    }
}
