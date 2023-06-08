<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DomainPlan: string
{
    case COLORME_SHOP_SUB_DOMAIN    = 'cmsp_sub_domain';
    case OWN_DOMAIN                 = 'own_domain';
    case OWN_SUB_DOMAIN             = 'own_sub_domain';

    public function name(): string
    {
        return match ($this) {
            self::COLORME_SHOP_SUB_DOMAIN   => 'shop-proサブドメイン',
            self::OWN_DOMAIN                => '独自ドメイン',
            self::OWN_SUB_DOMAIN            => '独自サブドメイン',
        };
    }
}
