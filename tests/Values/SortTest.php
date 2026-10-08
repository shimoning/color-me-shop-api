<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Values;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\SortDirection;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\Sort;

class SortTest extends TestCase
{
    public function test_方向省略時は昇順になる(): void
    {
        $sort = new Sort('make_date');

        $this->assertSame('make_date', $sort->getField());
        $this->assertSame(SortDirection::ASC, $sort->getDirection());
        $this->assertSame('make_date', $sort->get());
    }

    public function test_方向省略時の先頭ハイフンは降順として取り除く(): void
    {
        $sort = new Sort('-sales_price');

        $this->assertSame('sales_price', $sort->getField());
        $this->assertSame(SortDirection::DESC, $sort->getDirection());
        $this->assertSame('-sales_price', $sort->get());
    }

    #[DataProvider('explicitDirectionProvider')]
    public function test_方向を明示できる(SortDirection $direction, string $expected): void
    {
        $sort = new Sort('unknown_column', $direction);

        $this->assertSame('unknown_column', $sort->getField());
        $this->assertSame($direction, $sort->getDirection());
        $this->assertSame($expected, $sort->get());
    }

    /** @return array<string, array{SortDirection, string}> */
    public static function explicitDirectionProvider(): array
    {
        return [
            '昇順' => [SortDirection::ASC, 'unknown_column'],
            '降順' => [SortDirection::DESC, '-unknown_column'],
        ];
    }

    public function test_方向明示時の先頭ハイフンを拒否する(): void
    {
        $this->expectException(ParameterException::class);

        new Sort('-make_date', SortDirection::DESC);
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_不正な形式を拒否する(string $field): void
    {
        $this->expectException(ParameterException::class);

        new Sort($field);
    }

    /** @return array<string, array{string}> */
    public static function invalidFieldProvider(): array
    {
        return [
            '空文字' => [''],
            'ハイフンのみ' => ['-'],
            'ハイフン2つ' => ['--x'],
            'カンマ' => ['make_date,sales_price'],
            '空白' => ['make date'],
            'タブ' => ["make\tdate"],
            '改行' => ["make\ndate"],
        ];
    }

    public function test_validateは列名の形式を判定する(): void
    {
        $sort = new Sort('make_date');

        $this->assertTrue($sort->validate('any_column'));
        $this->assertTrue($sort->validate('-any_column'));
        $this->assertFalse($sort->validate(''));
        $this->assertFalse($sort->validate('--any_column'));
        $this->assertFalse($sort->validate('any,column'));
        $this->assertFalse($sort->validate('any column'));
        $this->assertFalse($sort->validate(1));
    }
}
