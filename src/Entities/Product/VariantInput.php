<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * バリエーション更新 (PUT /v1/products/{product_id}/variants/{id}) の `variant` 入力。
 *
 * 公式 OpenAPI では全フィールドが nullable で、一部は明示的な `null` で未設定へ戻すと説明される。
 * コンストラクタ配列で明示したフィールドだけを送信し、明示した `null` も送信する (ADR 0014)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class VariantInput extends Entity implements RequestEntity
{
    protected ?int $stocks;
    protected ?int $fewNum;
    protected ?string $modelNumber;
    protected ?int $weight;
    protected ?int $optionPrice;
    protected ?int $optionMembersPrice;
    protected ?int $optionMarketPrice;
    protected ?int $optionCost;
}
