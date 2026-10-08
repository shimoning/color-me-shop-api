<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Advertising;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Product\Advertising\Limit as AdvertisingLimit;

/**
 * 商品広告一覧 GET の検索条件。product_ids は整数配列をカンマ区切りで送る。
 */
class SearchParameters extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'productIds' => ['array' => true, 'scalar' => 'int'],
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
