<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品本体に埋め込まれた追加画像。
 * 画像専用 GET の ProductImage と構造が異なる。
 * 出典: docs/api-product-structure.md (fa4bfbb)。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
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
