<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleApplication;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleCustomization;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDetail;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleSegment;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleShopCoupon;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleTotals;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class SaleResponseFieldsTest extends TestCase
{
    public function test_構造化された受注フィールドを専用Entityで取得する(): void
    {
        $sale = new Sale(self::fixtureData('sale_with_values'));

        $segment = $sale->getSegment();
        $this->assertInstanceOf(SaleSegment::class, $segment);
        $this->assertSame(1, $segment->getId());
        $this->assertSame('通常商品', $segment->getName());
        $this->assertSame(4434233, $segment->getParentSaleId());
        $this->assertTrue($segment->isSplitted());
        $this->assertSame(2000, $segment->getProductTotalPrice());
        $this->assertSame(700, $segment->getDeliveryTotalCharge());
        $this->assertSame(2700, $segment->getTotalPrice());
        $this->assertSame(0, $segment->getNoshiTotalCharge());
        $this->assertSame(200, $segment->getCardTotalCharge());
        $this->assertSame(0, $segment->getWrappingTotalCharge());
        $this->assertSame([4434233, 4434234], $segment->getSiblingsSaleIds());

        $totals = $sale->getTotals();
        $this->assertInstanceOf(SaleTotals::class, $totals);
        $this->assertSame(90, $totals->getNormalTaxAmount());
        $this->assertSame(13, $totals->getReducedTaxAmount());
        $this->assertSame(171, $totals->getDiscountAmountForNormalTax());
        $this->assertSame(29, $totals->getDiscountAmountForReducedTax());
        $this->assertSame(989, $totals->getTotalPriceWithNormalTax());
        $this->assertSame(171, $totals->getTotalPriceWithReducedTax());

        $application = $sale->getApplication();
        $this->assertInstanceOf(SaleApplication::class, $application);
        $this->assertSame('サンプルアプリ', $application->getName());

        $shopCoupon = $sale->getShopCoupon();
        $this->assertInstanceOf(SaleShopCoupon::class, $shopCoupon);
        $this->assertSame(123, $shopCoupon->getId());
        $this->assertSame('新規会員限定クーポン', $shopCoupon->getName());
        $this->assertSame('WELCOME10', $shopCoupon->getCode());
    }

    public function test_nullableな受注フィールドが欠損していればnullになる(): void
    {
        $sale = new Sale(self::fixtureData('sale_with_missing_fields'));

        $this->assertNull($sale->getSegment());
        $this->assertNull($sale->getTotals());
        $this->assertNull($sale->getApplication());
        $this->assertNull($sale->getShopCoupon());
    }

    public function test_nullableな受注フィールドの明示的なnullを保持する(): void
    {
        $sale = new Sale(self::fixtureData('sale_with_nulls'));

        $this->assertNull($sale->getSegment());
        $this->assertNull($sale->getTotals());
        $this->assertNull($sale->getApplication());
        $this->assertNull($sale->getShopCoupon());
    }

    #[DataProvider('invalidSaleFieldProvider')]
    public function test_受注フィールドの不正型は固有例外になる(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Sale::class . ' の API フィールド『' . $field . '』が不正です。',
        );

        new Sale([$field => $value]);
    }

    #[DataProvider('invalidNestedEntityFieldProvider')]
    public function test_受注のnested_Entity内部フィールドの不正型は実際のフィールドを示す固有例外になる(
        string $saleField,
        string $entityClass,
        string $entityField,
        mixed $value,
    ): void {
        try {
            new Sale([$saleField => [$entityField => $value]]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertStringContainsString(
                Sale::class . ' の API フィールド『' . $saleField . '』が不正です。',
                $exception->getMessage(),
            );

            $cause = $exception->getPrevious();
            $this->assertInstanceOf(InvalidFieldException::class, $cause);
            $this->assertStringContainsString(
                $entityClass . ' の API フィールド『' . $entityField . '』が不正です。',
                $cause->getMessage(),
            );
        }
    }

    public function test_受注明細の追加フィールドを取得する(): void
    {
        $detail = new SaleDetail(self::fixtureData('detail_with_values'));

        $this->assertTrue($detail->isTaxReduced());
        $this->assertCount(2, $detail->getCustomizations());
        $this->assertContainsOnlyInstancesOf(SaleCustomization::class, $detail->getCustomizations());
        $this->assertSame('名入れ', $detail->getCustomizations()[0]->getTitle());
        $this->assertSame('オリジナル名', $detail->getCustomizations()[0]->getValue());
        $this->assertSame('メッセージ', $detail->getCustomizations()[1]->getTitle());
        $this->assertSame('おめでとう', $detail->getCustomizations()[1]->getValue());
    }

    public function test_customizationsの空配列を保持する(): void
    {
        $detail = new SaleDetail(self::fixtureData('detail_with_empty_customizations'));

        $this->assertFalse($detail->isTaxReduced());
        $this->assertSame([], $detail->getCustomizations());
    }

    #[DataProvider('missingDetailFieldProvider')]
    public function test_受注明細の追加フィールドが欠損していれば固有例外になる(
        string $field,
        string $getter,
    ): void {
        $detail = new SaleDetail(self::fixtureData('detail_with_missing_fields'));

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleDetail::class . ' の API フィールド『' . $field . '』が欠損しています。',
        );

        $detail->{$getter}();
    }

    #[DataProvider('invalidDetailFieldProvider')]
    public function test_受注明細フィールドの不正型は固有例外になる(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SaleDetail::class . ' の API フィールド『' . $field . '』が不正です。',
        );

        new SaleDetail([$field => $value]);
    }

    public function test_customizations配列要素の内部フィールドが不正なら要素変換の固有例外になる(): void
    {
        $fields = self::fixtureData('invalid_customization_fields');

        try {
            new SaleDetail(['customizations' => [$fields]]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                SaleDetail::class
                    . ' の API フィールド『customizations』が不正です。'
                    . '配列要素を ' . SaleCustomization::class
                    . ' に変換できませんでした。原因: 配列要素を変換できませんでした。',
                $exception->getMessage(),
            );

            $cause = $exception->getPrevious();
            $this->assertInstanceOf(InvalidFieldException::class, $cause);
            $this->assertStringContainsString(
                SaleCustomization::class . ' の API フィールド『title』が不正です。',
                $cause->getMessage(),
            );
        }
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidSaleFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_sale_fields');
        $cases = [];
        foreach ($fields as $field => $value) {
            $cases[$field] = [$field, $value];
        }

        return $cases;
    }

    /** @return array<string, array{string, class-string, string, mixed}> */
    public static function invalidNestedEntityFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_nested_entity_fields');

        return [
            'SaleSegment: bool 型の splitted を厳密に検証' => [
                'segment',
                SaleSegment::class,
                'splitted',
                $fields['segment']['splitted'],
            ],
            'SaleTotals: snake_case 変換される int 型の normal_tax_amount を検証' => [
                'totals',
                SaleTotals::class,
                'normal_tax_amount',
                $fields['totals']['normal_tax_amount'],
            ],
            'SaleApplication: 唯一の string 型フィールド name を検証' => [
                'application',
                SaleApplication::class,
                'name',
                $fields['application']['name'],
            ],
            'SaleShopCoupon: int 型の識別子 id を検証' => [
                'shop_coupon',
                SaleShopCoupon::class,
                'id',
                $fields['shop_coupon']['id'],
            ],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function missingDetailFieldProvider(): array
    {
        return [
            'tax_reduced' => ['tax_reduced', 'isTaxReduced'],
            'customizations' => ['customizations', 'getCustomizations'],
        ];
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidDetailFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_detail_fields');
        $cases = [];
        foreach ($fields as $field => $value) {
            $cases[$field] = [$field, $value];
        }

        return $cases;
    }

    /** @return array<string, mixed> */
    private static function fixtureData(string $key): array
    {
        $fixture = self::fixtureArray('sales_response_fields.json');
        $data = $fixture[$key] ?? null;
        if (! \is_array($data)) {
            throw new \RuntimeException('Sales fixture に配列データがありません: ' . $key);
        }

        return $data;
    }
}
