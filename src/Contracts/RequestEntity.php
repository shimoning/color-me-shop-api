<?php

namespace Shimoning\ColorMeShopApi\Contracts;

/**
 * 利用者が送信する値を組み立てる Entity の印。
 *
 * Entity 基底はコンストラクタで受け取った既知フィールドを内部に記録し、
 * RequestEntity::toArrayRecursive() でそのフィールドだけを直列化する。
 * 応答 Entity と同じ null 初期化を共有しながら、未設定と明示 null を区別するためである。
 * 既存の setter 型入力は Entity::markRequestField() で同じ記録に追加する。
 *
 * FallbackEnum の未知値や番兵値を API リクエストに流さない。
 * OBJECT_FIELDS で構築する未マークの子 Entity にも厳格な検証が伝わる。
 */
interface RequestEntity
{
}
