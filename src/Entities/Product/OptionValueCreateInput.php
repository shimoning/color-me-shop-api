<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * オプション値作成 (POST /v1/products/{product_id}/options/{option_id}/values) の `option_value` 入力。
 * OptionCreateInput の `values` 要素としても使う。
 *
 * 公式 OpenAPI では `name` が required のため `null` は受け付けない (ADR 0014)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class OptionValueCreateInput extends Entity implements RequestEntity
{
    protected string $name;
}

// 0.14.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(OptionValueCreateInput::class);
