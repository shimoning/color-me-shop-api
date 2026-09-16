<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * SEOメタタグ情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
 *
 * 公式 OpenAPI（2026-09-16 確認）では title / keywords / description が nullable。
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
