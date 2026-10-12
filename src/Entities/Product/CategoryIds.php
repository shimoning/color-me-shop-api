<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品に含まれるカテゴリー ID の組。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
 */
class CategoryIds extends Entity
{
    protected int $idBig;
    protected int $idSmall;

    /**
     * id_big
     * @return int
     */
    public function getIdBig(): int
    {
        $this->assertFieldInitialized('idBig');
        return $this->idBig;
    }

    /**
     * id_small
     * @return int
     */
    public function getIdSmall(): int
    {
        $this->assertFieldInitialized('idSmall');
        return $this->idSmall;
    }
}
