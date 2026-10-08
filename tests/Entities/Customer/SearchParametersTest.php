<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

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

    public function test_fieldsをカンマ区切りのクエリ値へ変換する(): void
    {
        $parameters = new SearchParameters(['fields' => ['id', 'name']]);

        $this->assertSame(['id', 'name'], $parameters->toArray()['fields']);
        $this->assertSame('id,name', $parameters->toArrayRecursive()['fields']);
    }

    public function test_空のfieldsは送らない(): void
    {
        $parameters = new SearchParameters(['fields' => []]);

        $this->assertSame([], $parameters->toArray()['fields']);
        $this->assertSame([], $parameters->toArrayRecursive());
    }

    public function test_fieldsの文字列以外の要素を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['fields' => ['id', 1]]);
    }

    public function test_fieldsの非リストを拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['fields' => ['first' => 'id']]);
    }
}
