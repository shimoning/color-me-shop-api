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
 * 在庫一覧 GET の検索条件。
 * fields は文字列の配列で指定し、クエリではカンマ区切りで送る。
 *
 * 在庫 API は対象外パラメータと不正な display_state を黙って無視するため、
 * 商品検索とは専用クラスを分け、有効なパラメータ集合を型で限定する。
 *
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'limit' => ['value' => StockLimit::class],
        'ids' => ['array' => true, 'scalar' => 'int'],
        'fields' => ['array' => true, 'scalar' => 'string'],
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

    /** @return array<string, mixed> */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $parameters = parent::toArrayRecursive($ignoreNull);
        foreach (['ids', 'fields'] as $field) {
            if (isset($parameters[$field]) && is_array($parameters[$field])) {
                if ($parameters[$field] === []) {
                    unset($parameters[$field]);
                } else {
                    $parameters[$field] = implode(',', $parameters[$field]);
                }
            }
        }
        return $parameters;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
