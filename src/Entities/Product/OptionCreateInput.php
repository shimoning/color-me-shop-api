<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * オプション作成 (POST /v1/products/{product_id}/options) の `option` 入力。
 *
 * 公式 OpenAPI では `name` と `values` が required で、`values` の各要素は `name` を持つ object。
 * `values` は `[['name' => 'S'], ['name' => 'M']]` の形で指定し、要素は OptionValueCreateInput として検証する。
 * required のため `null` は受け付けない。指定しなかったフィールドは送信しない (ADR 0014)。
 *
 * `values` は公式 OpenAPI で array のため、連想配列 (JSON で object になる形) は構築時に
 * `InvalidFieldException` で拒否する (ProductInput の `group_ids` / `variants` と同じ扱い)。
 * 各要素は required の `name` を持つ object でなければならず、空配列や `name` のない配列は
 * (`OptionValueCreateInput` が空になり JSON で `[]` として送られてしまうため) 構築時に拒否する。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class OptionCreateInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'values' => ['array' => true, 'entity' => OptionValueCreateInput::class],
    ];

    protected string $name;
    /** @var list<OptionValueCreateInput> */
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
