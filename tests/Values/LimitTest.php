<?php

namespace Shimoning\ColorMeShopApi\Tests\Values;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

class LimitTest extends TestCase
{
    #[DataProvider('validProvider')]
    public function test_1から100までを受け付ける(int $input): void
    {
        $this->assertSame($input, (new Limit($input))->get());
    }

    public static function validProvider(): array
    {
        return [
            '下限' => [1],
            '中間' => [50],
            '上限' => [100],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_範囲外はParameterExceptionを投げる(int $input): void
    {
        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage('件数は 1 ~ 100 の間で指定してください。');

        new Limit($input);
    }

    public static function invalidProvider(): array
    {
        return [
            '下限の1つ下' => [0],
            '負数' => [-1],
            '上限の1つ上' => [101],
            '極端に大きい値' => [\PHP_INT_MAX],
        ];
    }

    public function test_validateは境界値を判定する(): void
    {
        $limit = new Limit(10);

        $this->assertFalse($limit->validate(0));
        $this->assertTrue($limit->validate(1));
        $this->assertTrue($limit->validate(100));
        $this->assertFalse($limit->validate(101));
    }
}
