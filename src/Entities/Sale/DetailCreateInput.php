<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 受注作成時の受注明細。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 */
class DetailCreateInput extends Entity implements RequestEntity
{
    public const REQUIRED_FIELDS = ['product_id', 'product_num'];

    protected int $productId;
    protected ?string $option1Value;
    protected ?string $option2Value;
    protected int $productNum;
    protected ?int $price;
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(DetailCreateInput::class);
