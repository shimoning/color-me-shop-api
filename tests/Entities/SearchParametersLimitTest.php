<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters as SaleSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;
use Shimoning\ColorMeShopApi\Values\Customer\Limit as CustomerLimit;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Product\Advertising\Limit as AdvertisingLimit;
use Shimoning\ColorMeShopApi\Values\Product\Limit as ProductLimit;
use Shimoning\ColorMeShopApi\Values\Product\Stock\Limit as StockLimit;
use Shimoning\ColorMeShopApi\Values\Product\Variant\Limit as VariantLimit;
use Shimoning\ColorMeShopApi\Values\Sale\Limit as SaleLimit;

class SearchParametersLimitTest extends TestCase
{
    /**
     * @param class-string<ProductSearchParameters|StockSearchParameters|VariantSearchParameters|AdvertisingSearchParameters|SaleSearchParameters|CustomerSearchParameters> $parametersClass
     * @param class-string<Limit> $limitClass
     */
    #[DataProvider('apiProvider')]
    public function test_整数をAPIごとの範囲で検証し整数で送る(
        string $parametersClass,
        string $limitClass,
        int $maximum,
    ): void {
        foreach ([1, $maximum] as $valid) {
            $this->assertSame(
                ['limit' => $valid],
                (new $parametersClass(['limit' => $valid]))->toArrayRecursive(),
            );
        }

        foreach ([0, $maximum + 1] as $invalid) {
            $this->expectInvalidLimit(static fn() => new $parametersClass(['limit' => $invalid]));
        }
    }

    /**
     * @param class-string<ProductSearchParameters|StockSearchParameters|VariantSearchParameters|AdvertisingSearchParameters|SaleSearchParameters|CustomerSearchParameters> $parametersClass
     * @param class-string<Limit> $limitClass
     */
    #[DataProvider('apiProvider')]
    public function test_Limitのインスタンスは受け付けない(
        string $parametersClass,
        string $limitClass,
        int $maximum,
    ): void {
        foreach ([new Limit(1), new $limitClass(1)] as $limit) {
            $this->expectInvalidLimit(
                static fn() => new $parametersClass(['limit' => $limit]),
            );
        }
    }

    #[DataProvider('apiProvider')]
    public function test_limit用setterを公開APIに追加しない(
        string $parametersClass,
        string $limitClass,
        int $maximum,
    ): void {
        $this->assertFalse(\method_exists($parametersClass, 'setLimit'));
    }

    /** @param callable(): mixed $callback */
    private function expectInvalidLimit(callable $callback): void
    {
        try {
            $callback();
            $this->fail('InvalidFieldException が投げられませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertSame('limit', $this->apiFieldFromMessage($exception->getMessage()));
        }
    }

    private function apiFieldFromMessage(string $message): string
    {
        $this->assertMatchesRegularExpression('/API フィールド『([^』]+)』が不正/', $message);
        \preg_match('/API フィールド『([^』]+)』が不正/', $message, $matches);

        return $matches[1];
    }

    /** @return iterable<string, array{class-string, class-string<Limit>, int}> */
    public static function apiProvider(): iterable
    {
        yield '商品' => [ProductSearchParameters::class, ProductLimit::class, 50];
        yield '在庫' => [StockSearchParameters::class, StockLimit::class, 50];
        yield 'バリエーション' => [VariantSearchParameters::class, VariantLimit::class, 100];
        yield '商品広告' => [AdvertisingSearchParameters::class, AdvertisingLimit::class, 250];
        yield '受注' => [SaleSearchParameters::class, SaleLimit::class, 100];
        yield '顧客' => [CustomerSearchParameters::class, CustomerLimit::class, 100];
    }
}
