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
 * `values` は `[['name' => 'S'], ['name' => 'M']]` の形で指定し、要素は OptionValueInput として検証する。
 * required のため `null` は受け付けない。指定しなかったフィールドは送信しない (ADR 0014)。
 *
 * `values` は公式 OpenAPI で array のため、連想配列 (JSON で object になる形) は構築時に
 * `InvalidFieldException` で拒否する (ProductInput の `group_ids` / `variants` と同じ扱い)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class OptionInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'values' => ['array' => true, 'entity' => OptionValueInput::class],
    ];

    protected string $name;
    /** @var list<OptionValueInput> */
    protected array $values;

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException `values` がリスト以外の配列、または要素が不正な場合
     */
    public function __construct(array $data)
    {
        if (isset($data['values']) && \is_array($data['values']) && ! \array_is_list($data['values'])) {
            throw InvalidFieldException::for(self::class, 'values', 'list<array{name: string}>', $data['values']);
        }
        parent::__construct($data);
    }
}
