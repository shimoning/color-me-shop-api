<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * SEOメタタグ情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
 *
 * 公式 OpenAPI（2026-09-16 確認）の
 * components.schemas.productCategory.properties.meta_tag.allOf[0].properties と
 * components.schemas.productCategoryChild.properties.meta_tag.allOf[0].properties で、
 * title / keywords / description が nullable として定義されている。
 * また、meta_tag.allOf[0] は type: object かつ additionalProperties: false と
 * 定義されている。ただし MetaTag は Entity の既存契約に従い、未知キーを無視し、
 * 生データを getRaw() に保持する。未知キーは型付きプロパティや配列化の対象外となる。
 */
class MetaTag extends Entity
{
    protected ?string $title;
    protected ?string $keywords;
    protected ?string $description;

    /**
     * タイトル
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * キーワード
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    /**
     * ページ概要
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
