<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Product\Category;

use Shimoning\ColorMeShopApi\Aliases;

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

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(CategoryDisplayState::class);
