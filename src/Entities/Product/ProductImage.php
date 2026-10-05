<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * GET /products/{id}/images の画像要素。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
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
