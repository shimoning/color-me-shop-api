<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;

/** 値フォールバックの要求文脈を検証するテストダブル。 */
class FallbackValueRequestEntity extends FallbackValueEntity implements RequestEntity
{
}
