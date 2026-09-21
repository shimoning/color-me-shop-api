<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
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
 * `display_state` は `GroupDisplayState` (`showing` / `hidden` / `members_only`) で、公式 OpenAPI の
 * グループ作成・更新 request の enum と一致する。実 API の観測 (2026-09-21) でも同じ3値が受理され、
 * 商品の `showing_for_members` / `sale_for_members` は 422 で拒否されたため、構築時に拒否する。
 * 応答の `Group` も同じ enum を使う (docs/enum-openapi-audit.md、ADR 0014)。
 *
 * 実 API の観測 (2026-09-21) では、`expl` は明示した `null` でクリアできた。一方 `meta_tag` は初回設定
 * (null から値へ) だけが永続化され、以後の PUT (一部キーのみ、全キー新値、全キー `null`、`meta_tag: null`) は
 * 応答には反映されるが GET では初回設定の値のままだった (API 側の挙動と考えられ、未解決)。
 * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
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
        'displayState' => ['enum' => GroupDisplayState::class],
        'metaTag' => ['allowNull' => true, 'entity' => MetaTagInput::class],
    ];

    protected ?string $name;
    protected ?string $expl;
    protected ?GroupDisplayState $displayState;
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
