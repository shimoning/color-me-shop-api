<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

if (\PHP_VERSION_ID >= 80400) {
    eval(<<<'PHP'
        namespace Shimoning\ColorMeShopApi\Tests\Doubles;

        class InheritedVirtualFieldEntity extends VirtualFieldEntity
        {
        }
        PHP);
}
