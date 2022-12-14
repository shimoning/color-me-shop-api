<?php

namespace Shimoning\ColorMeShopApi\Values;

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
