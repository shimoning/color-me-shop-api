<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * GET /products/{id}/images の画像要素。
 * 商品本体の追加画像 Image と構造が異なる。
 * 出典: docs/api-product-structure.md (fa4bfbb)。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class ProductImage extends Entity
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
