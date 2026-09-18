<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品広告一覧 GET の検索条件。product_ids は整数配列をカンマ区切りで送る。
 * limit の既定値は 50、OpenAPI 上の最大値は 250。
 */
class AdvertisingSearchParameters extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'displayState' => ['enum' => ProductDisplayState::class],
    ];

    /** @var list<int>|null */
    protected ?array $productIds;
    protected ?ProductDisplayState $displayState;
    protected ?int $limit;
    protected ?int $offset;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (isset($data['product_ids']) && is_array($data['product_ids'])) {
            foreach ($data['product_ids'] as $value) {
                if (! is_int($value)) {
                    throw InvalidFieldException::forArrayElement(
                        self::class, 'product_ids', 'int', new \TypeError('配列要素の型が不正です。'),
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
        if (isset($parameters['product_ids']) && is_array($parameters['product_ids'])) {
            if ($parameters['product_ids'] === []) {
                unset($parameters['product_ids']);
            } else {
                $parameters['product_ids'] = implode(',', $parameters['product_ids']);
            }
        }
        return $parameters;
    }
}
