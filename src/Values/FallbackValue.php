<?php

namespace Shimoning\ColorMeShopApi\Values;

/** 応答側で検証に通らない生値の保持を許可する値オブジェクト。 */
interface FallbackValue extends Value
{
    /**
     * 検証に通らない生の値から、フォールバックのインスタンスを作る。
     * 実装によってはコンストラクタを経由せずに生成する場合がある。
     * コンストラクタで初期化する状態を持つ実装（サブクラスを含む）は、
     * fallback() を上書きし、その状態を初期化する責任がある。
     */
    public static function fallback(string $value): static;

    /** 保持している値が API 仕様どおりかを判定する。 */
    public function isValid(): bool;
}
