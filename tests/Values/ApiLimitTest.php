<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Values;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\Customer\Limit as CustomerLimit;
use Shimoning\ColorMeShopApi\Values\Product\Advertising\Limit as AdvertisingLimit;
use Shimoning\ColorMeShopApi\Values\Product\Limit as ProductLimit;
use Shimoning\ColorMeShopApi\Values\Product\Stock\Limit as StockLimit;
use Shimoning\ColorMeShopApi\Values\Product\Variant\Limit as VariantLimit;
use Shimoning\ColorMeShopApi\Values\Sale\Limit as SaleLimit;

class ApiLimitTest extends TestCase
{
    /** @param class-string<\Shimoning\ColorMeShopApi\Values\Limit> $class */
    #[DataProvider('apiProvider')]
    public function test_APIごとの境界値を検証する(string $class, int $maximum): void
    {
        $this->assertSame(1, (new $class(1))->get());
        $this->assertSame($maximum, (new $class($maximum))->get());

        foreach ([0, $maximum + 1] as $invalid) {
            try {
                new $class($invalid);
                $this->fail('ParameterException が投げられませんでした。');
            } catch (ParameterException $exception) {
                $this->assertSame(
                    \sprintf('件数は 1 ~ %d の間で指定してください。 : %d', $maximum, $invalid),
                    $exception->getMessage(),
                );
            }
        }
    }

    /** @return iterable<string, array{class-string<\Shimoning\ColorMeShopApi\Values\Limit>, int}> */
    public static function apiProvider(): iterable
    {
        yield '商品' => [ProductLimit::class, 50];
        yield '在庫' => [StockLimit::class, 50];
        yield 'バリエーション' => [VariantLimit::class, 100];
        yield '商品広告' => [AdvertisingLimit::class, 250];
        yield '受注' => [SaleLimit::class, 100];
        yield '顧客' => [CustomerLimit::class, 100];
    }
}
