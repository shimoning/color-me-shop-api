<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 振込先口座の種別。
 */
enum KouzaType: string
{
    case SAVING = 'saving';  // 普通
    case CHECKING = 'checking';  // 当座
}
