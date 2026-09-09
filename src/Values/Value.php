<?php

namespace Shimoning\ColorMeShopApi\Values;

/**
 * API パラメータ用の値オブジェクトが実装するインターフェース。
 */
interface Value
{
    /**
     * バリデーション
     * @param mixed $value
     * @return boolean
     */
    public function validate(mixed $value): bool;

    /**
     * 値を取得
     * @return string|int
     */
    public function get(): string|int;
}
