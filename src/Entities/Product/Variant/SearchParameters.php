<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Variant;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品バリエーション一覧 GET の検索条件。
 *
 * 公式 OpenAPI との差分: 未記載の既定 limit は 10 (2026-09-18)。
 *
 * @see docs/api-product-structure.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    protected ?string $modelNumber;
    protected ?string $fields;
    protected ?int $limit;
    protected ?int $offset;
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
