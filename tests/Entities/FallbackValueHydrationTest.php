<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Doubles\FallbackValueEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\FallbackValueRequestEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\MixedFallbackValue;

class FallbackValueHydrationTest extends TestCase
{
    private const RAW = 'テストカナ&#12535;&#12536;&#12537;&#12538;';

    public static function nonStringValues(): array
    {
        return [
            '整数' => [123],
            '小数' => [1.5],
            '真' => [true],
            '偽' => [false],
            '配列' => [[]],
            'オブジェクト' => [new \stdClass()],
            'null' => [null],
        ];
    }

    #[DataProvider('nonStringValues')]
    public function test_非文字列の検証失敗は応答でもフォールバックせず原因例外を保持する(mixed $value): void
    {
        MixedFallbackValue::$fallbackCalls = 0;
        MixedFallbackValue::$lastException = null;

        try {
            new FallbackValueEntity(['mixed' => $value]);
            $this->fail('非文字列の検証失敗は応答でも拒否する必要があります。');
        } catch (InvalidFieldException $exception) {
            $this->assertInstanceOf(ParameterException::class, $exception->getPrevious());
            $this->assertSame(MixedFallbackValue::$lastException, $exception->getPrevious());
            $this->assertSame(0, MixedFallbackValue::$fallbackCalls);
        }
    }

    public function test_文字列の検証失敗は同じ値オブジェクトでフォールバックする(): void
    {
        MixedFallbackValue::$fallbackCalls = 0;
        MixedFallbackValue::$lastException = null;

        $entity = new FallbackValueEntity(['mixed' => self::RAW]);

        $this->assertInstanceOf(ParameterException::class, MixedFallbackValue::$lastException);
        $this->assertSame(1, MixedFallbackValue::$fallbackCalls);
        $this->assertSame(['mixed' => self::RAW], $entity->toArrayRecursive());
    }

    public function test_実測した応答のフリガナをデコードせず保持する(): void
    {
        $customer = new Customer(['furigana' => self::RAW]);

        $this->assertSame(self::RAW, $customer->getFurigana()->get());
        $this->assertFalse($customer->getFurigana()->isValid());
        $this->assertSame(self::RAW, $customer->getRaw()['furigana']);
        $this->assertSame(self::RAW, $customer->toArrayRecursive()['furigana']);
    }

    public static function objectFields(): array
    {
        return [
            '単体' => [['value' => self::RAW]],
            'クラス名' => [['bare' => self::RAW]],
            '値配列' => [['values' => ['カナ', self::RAW]]],
            '子' => [['child' => ['value' => self::RAW]]],
            '孫' => [['child' => ['child' => ['value' => self::RAW]]]],
            '子配列' => [['children' => [['value' => 'カナ'], ['value' => self::RAW]]]],
        ];
    }

    #[DataProvider('objectFields')]
    public function test_応答の各生成経路で生値を保持する(array $data): void
    {
        $entity = new FallbackValueEntity($data);

        $this->assertSame($data, $entity->toArrayRecursive());
    }

    #[DataProvider('objectFields')]
    public function test_要求の各生成経路では拒否して文脈を復元する(array $data): void
    {
        try {
            new FallbackValueRequestEntity($data);
            $this->fail('要求側では不正な値を拒否する必要があります。');
        } catch (InvalidFieldException) {
            $this->assertSame($data, (new FallbackValueEntity($data))->toArrayRecursive());
        }
    }

    public static function requestClasses(): array
    {
        return [[SearchParameters::class], [CustomerCreateInput::class], [CustomerUpdateInput::class]];
    }

    #[DataProvider('requestClasses')]
    public function test_顧客の要求では実測値を拒否する(string $class): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('furigana');

        new $class(['furigana' => self::RAW]);
    }

    public static function invalidFields(): array
    {
        return [
            '対象外Limit' => [['limit' => 0]],
            '対象外DateTime' => [['date' => 'invalid-date']],
            '文字列以外' => [['value' => []]],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_対象外の値と型不一致は応答でも拒否する(array $data): void
    {
        $this->expectException(InvalidFieldException::class);

        new FallbackValueEntity($data);
    }
}
