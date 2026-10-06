<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Option\Value;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * オプション値作成 (POST /v1/products/{product_id}/options/{option_id}/values) の `option_value` 入力。
 * OptionCreateInput の `values` 要素としても使う。
 *
 * 公式 OpenAPI では `name` が required のため `null` は受け付けない。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/adr/0014-model-product-write-api.md
 */
class ValueCreateInput extends Entity implements RequestEntity
{
    protected string $name;
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(ValueCreateInput::class);
