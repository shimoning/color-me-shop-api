<?php

namespace Shimoning\ColorMeShopApi\Entities\Common;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * SEOメタタグ情報。
 *
 * `title` / `keywords` / `description` は nullable。未知のキーは型付きプロパティと配列化から除外し、
 * 生データには保持する。
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(MetaTag::class);
