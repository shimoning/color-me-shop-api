<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品グループ・商品カテゴリーの作成・更新で送る `meta_tag` (SEO メタタグ) の入力。
 *
 * 公式 OpenAPI の `group.meta_tag` / `category.meta_tag` は `title` / `keywords` / `description` を持つ
 * object で、3つとも `string` かつ `nullable` (`additionalProperties: false`)。
 * `GroupInput` / `CategoryInput` / `CategoryChildInput` の `meta_tag` にネストした連想配列として指定し、
 * 明示したキーだけを送信する。明示した `null` も送信する (ADR 0014)。
 * 応答側の `MetaTag` とは直列化契約が異なる (応答側は `null` を省略する) ため別の Entity にしている。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class MetaTagInput extends Entity implements RequestEntity
{
    protected ?string $title;
    protected ?string $keywords;
    protected ?string $description;

    public const SHAPE = 'array{title?: ?string, keywords?: ?string, description?: ?string}';

    /**
     * 親の入力 Entity が `meta_tag` に受け取った値の形状を、構築前に検証する。
     *
     * `title` / `keywords` / `description` のいずれも持たない配列 (空配列やリスト) は、
     * `MetaTagInput` が空になり JSON で object ではなく `[]` として送られてしまうため拒否する。
     * 各値の型は `MetaTagInput` の構築時に検証され、配列以外の値も同じく親の構築時に
     * `InvalidFieldException` になる。`null` は親側で明示 `null` として送信する。
     *
     * @param class-string<Entity> $ownerClass `meta_tag` を持つ入力 Entity
     * @throws InvalidFieldException `meta_tag` が公式 OpenAPI のキーを1つも持たない配列の場合
     */
    public static function assertOwnerField(string $ownerClass, mixed $value): void
    {
        if (! \is_array($value)) {
            return;
        }
        foreach (['title', 'keywords', 'description'] as $key) {
            if (\array_key_exists($key, $value)) {
                return;
            }
        }

        throw InvalidFieldException::for($ownerClass, 'meta_tag', self::SHAPE, $value);
    }
}
