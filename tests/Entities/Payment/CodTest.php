<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Payment;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Payment\Cod;
use Shimoning\ColorMeShopApi\Entities\Payment\CodFee;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class CodTest extends TestCase
{
    public function test_実測した3種類の代引き設定を構築する(): void
    {
        $payments = self::observedPayments();
        $fixed = (new Payment($payments[0]))->getCod();
        $tiered = (new Payment($payments[1]))->getCod();
        $withMaximum = (new Payment($payments[2]))->getCod();

        $this->assertNotNull($fixed);
        $this->assertFalse($fixed->getChangeable());
        $this->assertNull($fixed->getFees());
        $this->assertNull($fixed->getFeeMax());
        $this->assertNull($fixed->getChangeableByTotal());
        $this->assertSame(['changeable' => false], $fixed->toArrayRecursive());
        $this->assertSame([
            'changeable' => false,
            'fees' => null,
            'fee_max' => null,
            'changeable_by_total' => null,
        ], $fixed->toArrayRecursive(false));

        $this->assertNotNull($tiered);
        $this->assertTrue($tiered->getChangeable());
        $this->assertSame(500, $tiered->getFees()[0]->getUpperLimit());
        $this->assertSame(200, $tiered->getFees()[0]->getFee());
        $this->assertNull($tiered->getFeeMax());
        $this->assertFalse($tiered->getChangeableByTotal());
        $this->assertSame($payments[1]['cod'], $tiered->getRaw());
        $this->assertSame([
            'changeable' => true,
            'fees' => [['upper_limit' => 500, 'fee' => 200]],
            'changeable_by_total' => false,
        ], $tiered->toArrayRecursive());

        $this->assertNotNull($withMaximum);
        $fees = $withMaximum->getFees();
        $this->assertCount(2, $fees);
        $this->assertContainsOnlyInstancesOf(CodFee::class, $fees);
        $this->assertSame(300, $fees[0]->getUpperLimit());
        $this->assertSame(100, $fees[0]->getFee());
        $this->assertSame(500, $fees[1]->getUpperLimit());
        $this->assertSame(70, $fees[1]->getFee());
        $this->assertSame(10, $withMaximum->getFeeMax());
        $this->assertTrue($withMaximum->getChangeableByTotal());
        $this->assertSame($fees, $withMaximum->toArray()['fees']);
        $this->assertSame([
            'changeable' => true,
            'fees' => [
                ['upper_limit' => 300, 'fee' => 100],
                ['upper_limit' => 500, 'fee' => 70],
            ],
            'fee_max' => 10,
            'changeable_by_total' => true,
        ], $withMaximum->toArrayRecursive());
    }

    public function test_明示nullと空リストを区別する(): void
    {
        $null = new Cod([
            'changeable' => true,
            'fees' => null,
            'fee_max' => null,
            'changeable_by_total' => null,
        ]);
        $empty = new Cod(['changeable' => true, 'fees' => []]);

        $this->assertNull($null->getFees());
        $this->assertNull($null->toArray()['fees']);
        $this->assertArrayNotHasKey('fees', $null->toArrayRecursive());
        $this->assertNull($null->toArrayRecursive(false)['fees']);
        $this->assertSame([], $empty->getFees());
        $this->assertSame([], $empty->toArray()['fees']);
        $this->assertSame([], $empty->toArrayRecursive()['fees']);
    }

    #[DataProvider('invalidFeesProvider')]
    public function test_不正な手数料区分を位置付きで報告する(mixed $fees, string $field): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);

        new Cod(['changeable' => true, 'fees' => $fees]);
    }

    public static function invalidFeesProvider(): array
    {
        return [
            '要素不足' => [[[300, 100], [500]], 'fees[1]'],
            '要素過多' => [[[300, 100, 50]], 'fees[0]'],
            '非整数' => [[[300, '100']], 'fees[0]'],
            '非配列' => [[[300, 100], 'invalid'], 'fees[1]'],
            '内側が連想配列' => [[['upper_limit' => 300, 'fee' => 100]], 'fees[0]'],
            '外側が連想配列' => [['first' => [300, 100]], 'fees'],
            '外側が疎な配列' => [[1 => [300, 100]], 'fees'],
            '外側が文字列' => ['invalid', 'fees'],
            '外側がfalse' => [false, 'fees'],
        ];
    }

    #[DataProvider('invalidNullableFieldProvider')]
    public function test_null以外の型不一致は受け入れない(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);

        new Cod(['changeable' => true, $field => $value]);
    }

    public static function invalidNullableFieldProvider(): array
    {
        return [
            'fee_maxが文字列' => ['fee_max', '10'],
            'changeable_by_totalが整数' => ['changeable_by_total', 0],
        ];
    }

    private static function observedPayments(): array
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../../Fixtures/cod_payments.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
