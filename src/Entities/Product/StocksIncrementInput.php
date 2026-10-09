<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品在庫数を相対更新する `{"increment": int}` 入力。
 *
 * 公式 OpenAPI の object 定義に合わせ、`increment` を必須とし、それ以外のキーを拒否する。
 */
class StocksIncrementInput extends Entity implements RequestEntity
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(StocksIncrementInput::class);
