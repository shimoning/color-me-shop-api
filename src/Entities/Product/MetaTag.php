<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * SEOメタタグ情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
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
