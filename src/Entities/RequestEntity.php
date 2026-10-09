<?php

namespace Shimoning\ColorMeShopApi\Entities;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * 利用者が送信する値を組み立てる Entity の印。
 *
 * コンストラクタまたは setter で明示したフィールドだけを直列化し、JSON ボディでは明示した `null` も
 * 送信する。GET の検索条件では `null` をクエリから省略する。未知の enum 値と番兵値は拒否する。
 *
 * @see docs/adr/0014-model-product-write-api.md
 * @see docs/implementation-notes.md
 */
interface RequestEntity
{
}

// RequestEntity 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(RequestEntity::class);
