<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Product\Limit as ProductLimit;

/**
 * 商品一覧 (GET /v1/products) の検索条件。
 *
 * ids / group_ids は整数の配列、fields は文字列の配列で指定し、
 * クエリではカンマ区切りで送る。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'makeDateMin' => ['value' => DateTime::class],
        'makeDateMax' => ['value' => DateTime::class],
        'updateDateMin' => ['value' => DateTime::class],
        'updateDateMax' => ['value' => DateTime::class],
        'ids' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'groupIds' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'fields' => ['array' => true, 'scalar' => 'string', 'delimiter' => ','],
        'limit' => ['value' => ProductLimit::class, 'allowNull' => true],
    ];

    /** @var list<int>|null */
    protected ?array $ids;
    protected ?int $categoryIdBig;
    protected ?int $categoryIdSmall;
    /** @var list<int>|null */
    protected ?array $groupIds;
    protected ?string $modelNumber;
    protected ?string $name;
    protected ?ProductDisplayState $displayState;
    protected ?int $stocks;
    protected ?bool $stockManaged;
    protected ?bool $recentZeroStocks;
    protected ?DateTime $makeDateMin;
    protected ?DateTime $makeDateMax;
    protected ?DateTime $updateDateMin;
    protected ?DateTime $updateDateMax;
    protected ?int $salesPriceMin;
    protected ?int $salesPriceMax;
    protected ?int $priceMin;
    protected ?int $priceMax;
    protected ?int $membersPriceMin;
    protected ?int $membersPriceMax;
    protected ?string $janCode;
    protected ?string $sort;
    /**
     * 応答に含める商品フィールドの一覧。
     * fields を絞った応答では、省略された nullable フィールドの getter は null を返す。
     * 省略された非 nullable フィールドの getter は MissingFieldException を投げる。
     *
     * @var list<string>|null
     */
    protected ?array $fields;
    protected ?Limit $limit;
    protected ?int $offset;

    public function getDisplayState(): ?ProductDisplayState
    {
        return $this->displayState;
    }

}
