<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品グループの作成 (POST /v1/groups) と更新 (PUT /v1/groups/{id}) の `group` 入力。
 *
 * 公式 OpenAPI の両 `group` object の和集合を表し、作成と更新で共用する (ADR 0014)。
 * `parent_group_id` は作成側にだけある。どの操作でどのフィールドが有効かは公式 API 契約に従って
 * 利用者が選ぶ。`group` 自体は required だが、子プロパティに required 指定はない。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信し、明示した `null` も送信する。
 * - 指定しなかったフィールドは送信しない。
 * - 公式 OpenAPI で nullable なのは `expl` と `parent_group_id` と `meta_tag` の各値だけだが、
 *   ProductInput と同じく全フィールドを nullable にし、`null` の受理は API 側に委ねる。
 *
 * `display_state` について: 公式 OpenAPI のグループ作成・更新 request は `showing` / `hidden` /
 * `members_only` の3値を列挙し、グループ応答 (`ProductDisplayState` の `showing` / `hidden` /
 * `showing_for_members` / `sale_for_members`) と一致しない。商品入力の実測では応答側の4値が受理され
 * `members_only` は 422 だった一方、グループ入力の受理値は実 API で未確認である。本 Entity は応答と
 * 同じ `ProductDisplayState` を使い、`members_only` は構築時に拒否する。smoke test で受理値が確定したら
 * 更新する (docs/enum-openapi-audit.md、ADR 0014)。
 *
 * `meta_tag` は `MetaTagInput` へ変換する。`title` / `keywords` / `description` のいずれも持たない配列は
 * JSON で `[]` になり OpenAPI の object 定義に合わないため、構築時に `InvalidFieldException` で拒否する。
 * 値の範囲 (`maxLength` など) は API 側の検証に委ね、ライブラリでは検証しない。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class GroupInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'metaTag' => ['allowNull' => true, 'entity' => MetaTagInput::class],
    ];

    protected ?string $name;
    protected ?string $expl;
    protected ?ProductDisplayState $displayState;
    /** @var int|null 作成専用 */
    protected ?int $parentGroupId;
    protected ?MetaTagInput $metaTag;

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException 値の型や `meta_tag` の形状が公式 OpenAPI の定義に合わない場合
     */
    public function __construct(array $data)
    {
        MetaTagInput::assertOwnerField(self::class, $data['meta_tag'] ?? null);
        parent::__construct($data);
    }
}
