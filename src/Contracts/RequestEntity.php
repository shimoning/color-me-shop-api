<?php

namespace Shimoning\ColorMeShopApi\Contracts;

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
