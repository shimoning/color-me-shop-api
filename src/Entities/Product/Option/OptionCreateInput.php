<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Option;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * オプション作成 (POST /v1/products/{product_id}/options) の `option` 入力。
 *
 * `name` と `values` は必須で `null` を受け付けない。`values` は `name` を持つ object のリストで指定し、
 * 連想配列、空の要素、`name` のない要素は構築時に `InvalidFieldException` で拒否する。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/adr/0014-model-product-write-api.md
 */
class OptionCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'values' => ['array' => true, 'entity' => ValueCreateInput::class],
    ];

    protected string $name;
    /** @var list<ValueCreateInput> */
    protected array $values;

    private const VALUES_SHAPE = 'list<array{name: string}>';

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException `values` がリスト以外の配列、または要素が `name` を持つ配列でない場合
     */
    public function __construct(array $data)
    {
        if (isset($data['values']) && \is_array($data['values'])) {
            self::assertValues($data['values']);
        }
        parent::__construct($data);
    }

    /** @param array<mixed> $values */
    private static function assertValues(array $values): void
    {
        if (! \array_is_list($values)) {
            throw InvalidFieldException::for(self::class, 'values', self::VALUES_SHAPE, $values);
        }
        foreach ($values as $value) {
            if (! \is_array($value) || ! \array_key_exists('name', $value)) {
                throw InvalidFieldException::forArrayElement(
                    self::class,
                    'values',
                    'array{name: string}',
                    new \TypeError('オプション値の要素は name を持つ配列である必要があります。'),
                );
            }
        }
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(OptionCreateInput::class);
