<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class SearchParametersTest extends TestCase
{
    public function test_idsの非リストを拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SearchParameters::class . ' の API フィールド『ids』が不正です。'
            . 'list<int> を期待しましたが array でした。',
        );

        new SearchParameters(['ids' => [1 => 101]]);
    }
}
