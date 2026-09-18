<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\DeliveryMethodType;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;
use Shimoning\ColorMeShopApi\Constants\KouzaType;
use Shimoning\ColorMeShopApi\Constants\PaymentType;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Payment\Financial;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters as SalesSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class FallbackEnumHydrationTest extends TestCase
{
    /**
     * @return array<string, array{class-string<Entity>, string, string, int|string, int|string, class-string<FallbackEnum>, \BackedEnum}>
     */
    public static function responseFields(): array
    {
        return [
            'PaymentType' => [Payment::class, 'type', 'getType', 99, 0, PaymentType::class, PaymentType::COD],
            'Sex' => [Customer::class, 'sex', 'getSex', 'other_sex', 'male', Sex::class, Sex::MALE],
            'KouzaType' => [Financial::class, 'kouza_type', 'getKouzaType', 'current', 'saving', KouzaType::class, KouzaType::SAVING],
            'DeliveryMethodType' => [Delivery::class, 'method_type', 'getMethodType', 'new_carrier', 'other', DeliveryMethodType::class, DeliveryMethodType::OTHER],
        ];
    }

    #[DataProvider('responseFields')]
    public function test_応答の未知値は番兵に変換し生値を残す(
        string $class,
        string $field,
        string $getter,
        int|string $unknown,
        int|string $known,
        string $enum,
        \BackedEnum $knownCase,
    ): void {
        $entity = new $class([$field => $unknown]);

        $this->assertSame($enum::fallbackCase(), $entity->$getter());
        $this->assertSame($unknown, $entity->getRaw()[$field]);
        $this->assertSame($enum::fallbackCase()->value, $entity->toArrayRecursive()[$field]);
    }

    #[DataProvider('responseFields')]
    public function test_応答の既知値は従来のcaseになる(
        string $class,
        string $field,
        string $getter,
        int|string $unknown,
        int|string $known,
        string $enum,
        \BackedEnum $knownCase,
    ): void {
        $entity = new $class([$field => $known]);

        $this->assertSame($knownCase, $entity->$getter());
        $this->assertSame($known, $entity->getRaw()[$field]);
        $this->assertSame($known, $entity->toArrayRecursive()[$field]);
    }

    public function test_Sale内のCustomerでも未知の性別を番兵にする(): void
    {
        $sale = new Sale(['customer' => ['sex' => 'new_value']]);

        $this->assertSame(Sex::UNKNOWN, $sale->getCustomer()->getSex());
        $this->assertSame('new_value', $sale->getRaw()['customer']['sex']);
        $this->assertSame('new_value', $sale->getCustomer()->getRaw()['sex']);
        $this->assertSame('__unknown__', $sale->toArrayRecursive()['customer']['sex']);
    }

    /** @return array<string, array{class-string<Entity>, string, int|string}> */
    public static function requestFields(): array
    {
        return [
            'CustomerSearchParameters' => [SearchParameters::class, 'sex', 'new_value'],
            'SalesSearchParameters' => [SalesSearchParameters::class, 'accepted_mail_state', 'new_value'],
            'SaleUpdater' => [SaleUpdater::class, 'point_state', 'new_value'],
            'SaleDeliveryUpdater' => [SaleDeliveryUpdater::class, 'pref_id', 999],
        ];
    }

    #[DataProvider('requestFields')]
    public function test_要求Entityでは未知のenum値を拒否する(string $class, string $field, int|string $value): void
    {
        $this->expectException(InvalidFieldException::class);

        new $class([$field => $value]);
    }

    public function test_要求Entityでは番兵値の送信も拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['sex' => '__unknown__']);
    }

    public function test_要求Entityの未マークの子では未知のenum値を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new NestedRequestRoot(['child' => ['sex' => 'new_value']]);
    }

    public function test_要求Entityの配列内の未マークの子でも未知のenum値を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new NestedRequestRoot(['children' => [['sex' => 'new_value']]]);
    }

    public function test_要求Entityの連想配列形式の子でも未知のenum値を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new NestedRequestRoot(['children' => ['sex' => 'new_value']]);
    }

    public function test_要求Entityの二段下の未マークの子でも未知のenum値を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new NestedRequestRoot(['child' => ['grandchild' => ['sex' => 'new_value']]]);
    }

    public function test_要求Entityの二段下の未マークの子では番兵値も拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new NestedRequestRoot(['child' => ['grandchild' => ['sex' => '__unknown__']]]);
    }

    public function test_応答Entityの同じ子は未知値を番兵に変換する(): void
    {
        $response = new NestedResponseRoot(['child' => ['grandchild' => ['sex' => 'new_value']]]);

        $this->assertSame('new_value', $response->getRaw()['child']['grandchild']['sex']);
        $this->assertSame('__unknown__', $response->toArrayRecursive()['child']['grandchild']['sex']);
    }

    public function test_要求Entityの構築失敗後も応答文脈に戻る(): void
    {
        try {
            new NestedRequestRoot(['child' => ['grandchild' => ['sex' => 'new_value']]]);
            $this->fail('要求側の未知値を拒否する必要があります。');
        } catch (InvalidFieldException) {
            $response = new NestedResponseRoot(['child' => ['sex' => 'new_value']]);
            $this->assertSame('__unknown__', $response->toArrayRecursive()['child']['sex']);
        }
    }

    public function test_数値enumへ文字列を渡す型不一致は拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new Payment(['type' => 'new_payment']);
    }

    public function test_数値enumへ数値文字列を渡す型不一致は拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new Payment(['type' => '0']);
    }

    public function test_文字列enumへ数値を渡す型不一致は拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new Customer(['sex' => 99]);
    }
}

/** 要求文脈が未マークの子と孫へ伝わることを固定するテスト用 Entity。 */
final class NestedRequestRoot extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'child' => ['entity' => NestedChild::class],
        'children' => ['array' => true, 'entity' => NestedChild::class],
    ];

    protected NestedChild $child;
    /** @var list<NestedChild> */
    protected array $children;
}

final class NestedResponseRoot extends Entity
{
    public const OBJECT_FIELDS = ['child' => ['entity' => NestedChild::class]];

    protected NestedChild $child;
}

final class NestedChild extends Entity
{
    public const OBJECT_FIELDS = [
        'sex' => ['enum' => Sex::class],
        'grandchild' => ['entity' => NestedGrandchild::class],
    ];

    protected Sex $sex;
    protected NestedGrandchild $grandchild;
}

final class NestedGrandchild extends Entity
{
    public const OBJECT_FIELDS = ['sex' => ['enum' => Sex::class]];

    protected Sex $sex;
}
