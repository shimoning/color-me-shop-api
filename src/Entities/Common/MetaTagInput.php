<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Common;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品グループ・商品カテゴリーの作成・更新で送る `meta_tag` (SEO メタタグ) の入力。
 *
 * `title` / `keywords` / `description` のうち明示したキーだけを送信し、明示した `null` も送信する。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/adr/0014-model-product-write-api.md
 */
class MetaTagInput extends Entity implements RequestEntity
{
    protected ?string $title;
    protected ?string $keywords;
    protected ?string $description;

    public const SHAPE = 'array{title?: ?string, keywords?: ?string, description?: ?string}';

    /** 公式 OpenAPI の `meta_tag` が持つキー (`additionalProperties: false`)。 */
    private const KEYS = ['title', 'keywords', 'description'];

    /**
     * 親の入力 Entity が `meta_tag` に受け取った値の形状を、構築前に検証する。
     *
     * 空配列、未知のキー、配列以外の値を拒否する。`null` は明示値として送信する。
     *
     * @param class-string<Entity> $ownerClass `meta_tag` を持つ入力 Entity
     * @throws InvalidFieldException `meta_tag` が公式 OpenAPI のキーを1つも持たない配列、または公式 OpenAPI にないキーを持つ配列の場合
     */
    public static function assertOwnerField(string $ownerClass, mixed $value): void
    {
        if (! \is_array($value)) {
            return;
        }
        $known = \array_intersect_key($value, \array_flip(self::KEYS));
        if ($known !== [] && \count($known) === \count($value)) {
            return;
        }

        throw InvalidFieldException::for($ownerClass, 'meta_tag', self::SHAPE, $value);
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(MetaTagInput::class);
