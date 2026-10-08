<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Variant;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Product\Variant\Limit as VariantLimit;

/**
 * 商品バリエーション一覧 (GET /v1/products/{product_id}/variants) の検索条件。
 *
 * fields は文字列の配列で指定し、クエリではカンマ区切りで送る。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'limit' => ['value' => VariantLimit::class, 'allowNull' => true],
        'fields' => ['array' => true, 'scalar' => 'string', 'delimiter' => ','],
    ];

    protected ?string $modelNumber;
    /** @var list<string>|null */
    protected ?array $fields;
    protected ?Limit $limit;
    protected ?int $offset;

}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
