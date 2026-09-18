<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Values\DateTime;

/**
 * 商品一覧 GET の検索条件。
 * ids / group_ids は OpenAPI の説明に従い整数配列をカンマ区切りで送る。
 * 商品一覧 limit の API 上限は 50 (docs/api-product-structure.md, fa4bfbb)。
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'makeDateMin' => ['value' => DateTime::class],
        'makeDateMax' => ['value' => DateTime::class],
        'updateDateMin' => ['value' => DateTime::class],
        'updateDateMax' => ['value' => DateTime::class],
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
    protected ?string $fields;
    protected ?int $limit;
    protected ?int $offset;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        foreach (['ids', 'group_ids'] as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                continue;
            }
            foreach ($data[$field] as $value) {
                if (! is_int($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, $field, 'int', new \TypeError('配列要素の型が不正です。'));
                }
            }
        }
        parent::__construct($data);
    }

    public function getDisplayState(): ?ProductDisplayState
    {
        return $this->displayState;
    }

    /** @return array<string, mixed> */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $parameters = parent::toArrayRecursive($ignoreNull);
        foreach (['ids', 'group_ids'] as $field) {
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
