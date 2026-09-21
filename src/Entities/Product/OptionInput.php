<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * オプション作成 (POST /v1/products/{product_id}/options) の `option` 入力。
 *
 * 公式 OpenAPI では `name` と `values` が required で、`values` の各要素は `name` を持つ object。
 * `values` は `[['name' => 'S'], ['name' => 'M']]` の形で指定し、要素は OptionValueInput として検証する。
 * required のため `null` は受け付けない。指定しなかったフィールドは送信しない (ADR 0014)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class OptionInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'values' => ['array' => true, 'entity' => OptionValueInput::class],
    ];

    protected string $name;
    /** @var list<OptionValueInput> */
    protected array $values;
}
