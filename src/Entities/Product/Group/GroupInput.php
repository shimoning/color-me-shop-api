<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Group;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Common\MetaTagInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品グループの作成 (POST /v1/groups) と更新 (PUT /v1/groups/{id}) の `group` 入力。
 *
 * 明示したフィールドだけを送信し、明示した `null` も送信する。`display_state` は
 * `showing` / `hidden` / `members_only` に限り、`meta_tag` は `MetaTagInput` へ変換する。
 * 不正な `display_state` または `meta_tag` の形状は構築時に `InvalidFieldException` で拒否する。
 * `parent_group_id` は作成時にだけ有効で、公式 OpenAPI の更新 request にはない。
 *
 * 公式 OpenAPI との差分: nullable 指定のないフィールドも `null` を指定でき、受理可否は API に委ねる。
 * `meta_tag` の更新が GET に永続化されない場合がある (2026-09-21)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 * @see docs/adr/0014-model-product-write-api.md
 */
class GroupInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => GroupDisplayState::class],
        'metaTag' => ['allowNull' => true, 'entity' => MetaTagInput::class],
    ];

    /**
     * 送信できる `display_state`。
     *
     * 公式 OpenAPI の作成・更新の request の定義と同じ 3 値。応答の定義にだけある残り 2 値は、
     * 実 API も 422 で拒否する (2026-09-21)。
     *
     * @see docs/api-product-structure.md
     */
    public const WRITABLE_DISPLAY_STATES = [
        GroupDisplayState::SHOWING,
        GroupDisplayState::HIDDEN,
        GroupDisplayState::MEMBER_ONLY,
    ];

    private const WRITABLE_DISPLAY_STATE_SHAPE = "'showing'|'hidden'|'members_only'";

    protected ?string $name;
    protected ?string $expl;
    protected ?GroupDisplayState $displayState;
    /** @var int|null 作成専用 */
    protected ?int $parentGroupId;
    protected ?MetaTagInput $metaTag;

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException 値の型や `meta_tag` の形状が公式 OpenAPI の定義に合わない場合、
     *                               または `display_state` が送信できる 3 値以外の場合
     */
    public function __construct(array $data)
    {
        MetaTagInput::assertOwnerField(self::class, $data['meta_tag'] ?? null);
        parent::__construct($data);
        $this->assertWritableDisplayState($data['display_state'] ?? null);
    }

    /**
     * 基底 Entity が enum へ変換した後の `display_state` が、送信できる 3 値かを検証する。
     * 未知の文字列や他 enum は基底の変換で既に `InvalidFieldException` になっている。
     */
    private function assertWritableDisplayState(mixed $given): void
    {
        if (! isset($this->displayState)) {
            return;
        }
        if (\in_array($this->displayState, self::WRITABLE_DISPLAY_STATES, true)) {
            return;
        }

        throw InvalidFieldException::for(self::class, 'display_state', self::WRITABLE_DISPLAY_STATE_SHAPE, $given);
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(GroupInput::class);
