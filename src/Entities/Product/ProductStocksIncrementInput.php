<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品在庫数を相対更新する `{"increment": int}` 入力。
 *
 * 公式 OpenAPI の object 定義に合わせ、`increment` を必須とし、それ以外のキーを拒否する。
 */
class ProductStocksIncrementInput extends Entity implements RequestEntity
{
    protected int $increment;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (\array_keys($data) !== ['increment']) {
            throw InvalidFieldException::for(
                self::class,
                'increment',
                'int',
                $data['increment'] ?? null,
            );
        }

        parent::__construct($data);
    }
}
