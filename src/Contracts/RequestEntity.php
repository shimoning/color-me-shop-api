<?php

namespace Shimoning\ColorMeShopApi\Contracts;

/**
 * 利用者が送信する値を組み立てる Entity の印。
 *
 * Entity 基底はコンストラクタで受け取った既知フィールドを内部に記録し、
 * RequestEntity::toArrayRecursive() でそのフィールドだけを直列化する。
 * 応答 Entity と同じ null 初期化を共有しながら、未設定と明示 null を区別するためである。
 * 既存の setter 型入力は Entity::markRequestField() で同じ記録に追加する。
 * 明示 null をワイヤへ送る契約は JSON ボディに使う入力 Entity に限る。
 * GET の SearchParameters は http_build_query() を通るため、明示 null もクエリから省略される。
 * 明示フィールド追跡の追加前に serialize された RequestEntity は追跡情報を持たないため、
 * 復元後は従来どおり null を省略し、初期化済みの非 null フィールドを直列化する。
 *
 * FallbackEnum の未知値や番兵値を API リクエストに流さない。
 * OBJECT_FIELDS で構築する未マークの子 Entity にも厳格な検証が伝わる。
 */
interface RequestEntity
{
}
