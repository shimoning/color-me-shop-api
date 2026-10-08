<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class SearchParametersTest extends TestCase
{
    public function test_配列の検索条件をカンマ区切りのクエリ値へ変換する(): void
    {
        $parameters = new SearchParameters([
            'ids' => [1001, 1002],
            'customer_ids' => [501, 502],
            'payment_ids' => [3, 4],
            'fields' => ['id', 'customer'],
        ]);

        $this->assertSame([1001, 1002], $parameters->toArray()['ids']);
        $this->assertSame([501, 502], $parameters->toArray()['customer_ids']);
        $this->assertSame([3, 4], $parameters->toArray()['payment_ids']);
        $this->assertSame(['id', 'customer'], $parameters->toArray()['fields']);
        $this->assertSame([
            'ids' => '1001,1002',
            'customer_ids' => '501,502',
            'payment_ids' => '3,4',
            'fields' => 'id,customer',
        ], $parameters->toArrayRecursive());
    }

    public function test_空の配列検索条件は送らない(): void
    {
        $parameters = new SearchParameters([
            'ids' => [],
            'customer_ids' => [],
            'payment_ids' => [],
            'fields' => [],
        ]);

        $this->assertSame([], $parameters->toArrayRecursive());
    }

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
