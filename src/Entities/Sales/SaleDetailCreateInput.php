<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 受注作成時の受注明細。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 */
class SaleDetailCreateInput extends Entity implements RequestEntity
{
    public const REQUIRED_FIELDS = ['product_id', 'product_num'];

    protected int $productId;
    protected ?string $option1Value;
    protected ?string $option2Value;
    protected int $productNum;
    protected ?int $price;
}
