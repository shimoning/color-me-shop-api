<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 顧客の性別。
 */
enum Sex: string
{
    case MALE   = 'male'; // 男性
    case FEMALE = 'female'; // 女性

    /**
     * 性別の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::MALE      => '男性',
            self::FEMALE    => '女性',
        };
    }
}
