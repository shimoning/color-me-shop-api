<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品本体に埋め込まれた追加画像。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 */
class Image extends Entity
{
    protected string $src;
    protected int $position;
    protected bool $mobile;

    /**
     * 画像URL
     * @return string
     */
    public function getSrc(): string
    {
        $this->assertFieldInitialized('src');
        return $this->src;
    }

    /**
     * 表示順
     * @return int
     */
    public function getPosition(): int
    {
        $this->assertFieldInitialized('position');
        return $this->position;
    }

    /**
     * モバイル用であるか否か
     * @return bool
     */
    public function getMobile(): bool
    {
        $this->assertFieldInitialized('mobile');
        return $this->mobile;
    }
}
