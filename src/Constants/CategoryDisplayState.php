<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 商品カテゴリーの表示状態。
 */
enum CategoryDisplayState: string
{
    case SHOWING        = 'showing'; // 掲載
    case HIDDEN         = 'hidden'; // 非掲載
    case MEMBER_ONLY    = 'members_only'; // 会員にのみ掲載

    /**
     * 表示状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::SHOWING       => '掲載状態',
            self::HIDDEN        => '非掲載状態',
            self::MEMBER_ONLY   => '会員にのみ掲載',
        };
    }
}
