<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Stock;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Product\Stock\Limit as StockLimit;

/**
 * 在庫一覧 (GET /v1/stocks) の検索条件。
 *
 * ids は整数の配列、fields は文字列の配列で指定し、クエリではカンマ区切りで送る。
 *
 * 在庫 API は対象外パラメータと不正な display_state を黙って無視するため、
 * 商品検索とはクラスを分け、有効なパラメータ集合を型で限定する。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'limit' => ['value' => StockLimit::class],
        'ids' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'fields' => ['array' => true, 'scalar' => 'string', 'delimiter' => ','],
    ];

    /** @var list<int>|null */
    protected ?array $ids;
    protected ?int $categoryIdBig;
    protected ?int $categoryIdSmall;
    protected ?string $modelNumber;
    protected ?string $name;
    protected ?ProductDisplayState $displayState;
    protected ?int $stocks;
    protected ?bool $recentZeroStocks;
    /** @var list<string>|null */
    protected ?array $fields;
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
