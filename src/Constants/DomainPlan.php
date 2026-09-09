<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * ショップで利用するドメインのプラン。
 */
enum DomainPlan: string
{
    case COLORME_SHOP_SUB_DOMAIN    = 'cmsp_sub_domain'; // shop-pro サブドメイン
    case OWN_DOMAIN                 = 'own_domain'; // 独自ドメイン
    case OWN_SUB_DOMAIN             = 'own_sub_domain'; // 独自サブドメイン

    /**
     * ドメインプランの日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::COLORME_SHOP_SUB_DOMAIN   => 'shop-proサブドメイン',
            self::OWN_DOMAIN                => '独自ドメイン',
            self::OWN_SUB_DOMAIN            => '独自サブドメイン',
        };
    }
}
