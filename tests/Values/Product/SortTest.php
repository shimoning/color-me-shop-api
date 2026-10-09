<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Values\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\SortDirection;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\Product\Sort;

class SortTest extends TestCase
{
    #[DataProvider('validSortProvider')]
    public function test_商品一覧で使用できる列名と方向を保持する(
        string $input,
        string $field,
        SortDirection $direction,
    ): void {
        $sort = new Sort($input);

        $this->assertInstanceOf(\Shimoning\ColorMeShopApi\Values\Sort::class, $sort);
        $this->assertSame($field, $sort->getField());
        $this->assertSame($direction, $sort->getDirection());
        $this->assertSame($input, $sort->get());
    }

    /** @return iterable<string, array{string, string, SortDirection}> */
    public static function validSortProvider(): iterable
    {
        foreach (['make_date', 'update_date', 'sales_price', 'price', 'members_price'] as $field) {
            yield $field . ' 昇順' => [$field, $field, SortDirection::ASC];
            yield $field . ' 降順' => ['-' . $field, $field, SortDirection::DESC];
        }
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_商品一覧で使用できない列名を拒否する(string $field): void
    {
        $this->expectException(ParameterException::class);

        new Sort($field);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidFieldProvider(): iterable
    {
        yield 'name' => ['name'];
        yield 'id' => ['id'];
        yield 'foo' => ['foo'];
        yield '大文字' => ['SALES_PRICE'];
        yield '降順の未対応列' => ['-name'];
    }

    public function test_例外メッセージに使用できる列名を含む(): void
    {
        try {
            new Sort('name');
            $this->fail('ParameterException が投げられませんでした。');
        } catch (ParameterException $exception) {
            foreach (['make_date', 'update_date', 'sales_price', 'price', 'members_price'] as $field) {
                $this->assertStringContainsString($field, $exception->getMessage());
            }
        }
    }

    public function test_validateは商品一覧で使用できる列名だけを受け付ける(): void
    {
        $sort = new Sort('make_date');

        foreach (['make_date', '-update_date', 'sales_price', '-price', 'members_price'] as $field) {
            $this->assertTrue($sort->validate($field));
        }
        foreach (['name', '-name', 'SALES_PRICE'] as $field) {
            $this->assertFalse($sort->validate($field));
        }
    }
}
