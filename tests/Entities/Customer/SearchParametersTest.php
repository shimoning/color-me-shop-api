<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;

class SearchParametersTest extends TestCase
{
    public function test_idsをカンマ区切りのクエリ値へ変換する(): void
    {
        $parameters = new SearchParameters(['ids' => [501, 502]]);

        $this->assertSame([501, 502], $parameters->toArray()['ids']);
        $this->assertSame('501,502', $parameters->toArrayRecursive()['ids']);
    }

    public function test_空のidsは送らない(): void
    {
        $parameters = new SearchParameters(['ids' => []]);

        $this->assertSame([], $parameters->toArray()['ids']);
        $this->assertSame([], $parameters->toArrayRecursive());
    }
}
