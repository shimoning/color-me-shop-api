<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Advertising;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Product\Advertising\Limit as AdvertisingLimit;

/**
 * 商品広告一覧 (GET /v1/product_advertisings) の検索条件。
 *
 * product_ids は整数の配列で指定し、クエリではカンマ区切りで送る。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'productIds' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'limit' => ['value' => AdvertisingLimit::class, 'allowNull' => true],
    ];

    /** @var list<int>|null */
    protected ?array $productIds;
    protected ?ProductDisplayState $displayState;
    protected ?Limit $limit;
    protected ?int $offset;

    public function getDisplayState(): ?ProductDisplayState
    {
        return $this->displayState;
    }

}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
