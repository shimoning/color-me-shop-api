<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Stock;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Values\Limit;

/**
 * 在庫一覧 GET の検索条件。
 * 在庫 API は対象外パラメータと不正な display_state を黙って無視するため、
 * 商品検索とは専用クラスを分け、有効なパラメータ集合を型で限定する。
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'limit' => ['value' => Limit::class],
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
    protected ?string $fields;
    protected ?Limit $limit;
    protected ?int $offset;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (isset($data['ids']) && is_array($data['ids'])) {
            foreach ($data['ids'] as $value) {
                if (! is_int($value)) {
                    throw InvalidFieldException::forArrayElement(
                        self::class,
                        'ids',
                        'int',
                        new \TypeError('配列要素の型が不正です。'),
                    );
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
        if (isset($parameters['ids']) && is_array($parameters['ids'])) {
            if ($parameters['ids'] === []) {
                unset($parameters['ids']);
            } else {
                $parameters['ids'] = implode(',', $parameters['ids']);
            }
        }
        return $parameters;
    }
}
