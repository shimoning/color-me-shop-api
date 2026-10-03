<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品更新の `variants` 配列要素。
 *
 * `option1_value` / `option2_value` / `stocks` はいずれも任意だが、JSON object として送るため
 * 少なくとも1キーを必要とする。`stocks` は int または ProductStocksIncrementInput で、
 * OpenAPI に nullable 指定がないため `null` は拒否する (ADR 0012)。
 */
class ProductVariantInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'stocks' => ['entity' => ProductStocksIncrementInput::class, 'orScalar' => 'int'],
    ];

    protected ?string $option1Value;
    protected ?string $option2Value;
    protected ProductStocksIncrementInput|int|null $stocks;

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
