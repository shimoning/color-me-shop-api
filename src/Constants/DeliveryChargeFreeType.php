<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DeliveryChargeFreeType: string
{
    case NOT_FREE       = 'not_free'; // 有料
    case FREE_TO_LIMIT  = 'free_to_limit'; // 注文金額が一定以上の場合は無料
    case FREE           = 'free'; // 無料

    public function name(): string
    {
        return match ($this) {
            self::NOT_FREE      => '請求する',
            self::FREE_TO_LIMIT => '請求する（無料となる上限金額あり）',
            self::FREE          => '請求しない',
        };
    }
}
