<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品更新の `variants` 配列要素。
 *
 * `option1_value` / `option2_value` / `stocks` はいずれも任意だが、JSON object として送るため
 * 少なくとも1キーを必要とする。`stocks` は int または StocksIncrementInput で、
 * OpenAPI に nullable 指定がないため `null` は拒否する。
 *
 * @see docs/adr/0012-allow-nullability-from-api-observations.md
 */
class VariantInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'stocks' => ['entity' => StocksIncrementInput::class, 'orScalar' => 'int'],
    ];

    protected ?string $option1Value;
    protected ?string $option2Value;
    protected StocksIncrementInput|int|null $stocks;

    private const KEYS = ['option1_value', 'option2_value', 'stocks'];
    private const SHAPE = 'array{option1_value?: string, option2_value?: string, stocks?: int|array{increment: int}}';

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (
            $data === []
            || \array_diff(\array_keys($data), self::KEYS) !== []
            || (\array_key_exists('option1_value', $data) && $data['option1_value'] === null)
            || (\array_key_exists('option2_value', $data) && $data['option2_value'] === null)
            || (\array_key_exists('stocks', $data) && $data['stocks'] === null)
        ) {
            throw InvalidFieldException::for(self::class, 'variant', self::SHAPE, $data);
        }

        parent::__construct($data);
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(VariantInput::class);
