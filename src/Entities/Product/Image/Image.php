<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Image;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * GET /products/{id}/images の画像要素。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 */
class Image extends Entity
{
    protected int $position;
    protected string $url;

    /**
     * position
     * @return int
     */
    public function getPosition(): int
    {
        $this->assertFieldInitialized('position');
        return $this->position;
    }

    /**
     * url
     * @return string
     */
    public function getUrl(): string
    {
        $this->assertFieldInitialized('url');
        return $this->url;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Image::class);
