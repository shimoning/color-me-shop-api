<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class VariantSearchParametersTest extends TestCase
{
    public function test_全検索条件をクエリ形式に変換する(): void
    {
        $parameters = new SearchParameters([
            'model_number' => 'TEST', 'fields' => ['id', 'model_number'], 'limit' => 100, 'offset' => 10,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $parameters);
        $this->assertSame([
            'model_number' => 'TEST', 'fields' => 'id,model_number', 'limit' => 100, 'offset' => 10,
        ], $parameters->toArrayRecursive());
        $this->assertSame(['id', 'model_number'], $parameters->toArray()['fields']);
    }

    public function test_未指定の条件はクエリに出さない(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
    }

    public function test_不正な条件の型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['limit' => '100']);
    }

    public function test_空のfieldsは送らない(): void
    {
        $parameters = new SearchParameters(['fields' => []]);

        $this->assertSame([], $parameters->toArray()['fields']);
        $this->assertSame([], $parameters->toArrayRecursive());
    }

    /** @dataProvider invalidFieldsProvider */
    public function test_不正なfieldsを拒否する(mixed $fields): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['fields' => $fields]);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            '文字列' => ['id,model_number'],
            '文字列でない要素' => [['id', 1]],
            'リストでない配列' => [['first' => 'id']],
        ];
    }
}
