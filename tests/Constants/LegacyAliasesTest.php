<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * 0.32.0 で移動した enum の旧名が、非推奨の別名として解決できることを固定する。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LegacyAliasesTest extends TestCase
{
    /**
     * @param class-string<\BackedEnum> $legacy
     * @param class-string<\BackedEnum> $current
     */
    #[DataProvider('aliasProvider')]
    public function test_旧名は新名のenumとしてすべての操作に利用できる(
        string $legacy,
        string $current,
    ): void {
        $this->assertFalse(\class_exists($legacy, false));
        $this->assertFalse(\enum_exists($legacy, false));
        $this->assertFalse(\class_exists($current, false));
        $this->assertFalse(\enum_exists($current, false));

        $this->assertTrue(\class_exists($legacy));
        $this->assertTrue(\enum_exists($legacy));
        $this->assertTrue(\class_exists($current));
        $this->assertTrue(\enum_exists($current));
        $this->assertSame($current, (new \ReflectionEnum($legacy))->getName());

        $case = (new \ReflectionEnum($current))->getCases()[0]->getValue();
        $this->assertInstanceOf(\BackedEnum::class, $case);
        $legacyCase = \constant($legacy . '::' . $case->name);
        $currentCase = \constant($current . '::' . $case->name);

        $this->assertSame($currentCase, $legacyCase);
        $this->assertSame($currentCase, $legacy::from($case->value));
        $this->assertSame($currentCase, $legacy::tryFrom($case->value));
        $this->assertInstanceOf($current, $legacyCase);

        /** @var callable(\BackedEnum): \BackedEnum $acceptLegacy */
        $acceptLegacy = eval(
            'return static function (\\' . $legacy . ' $value): \\BackedEnum { return $value; };'
        );
        $this->assertSame($currentCase, $acceptLegacy($currentCase));
    }

    /** @return array<string, array{class-string<\BackedEnum>, class-string<\BackedEnum>}> */
    public static function aliasProvider(): array
    {
        return [
            'ProductDisplayState' => [
                'Shimoning\ColorMeShopApi\Constants\ProductDisplayState',
                'Shimoning\ColorMeShopApi\Constants\Product\DisplayState',
            ],
            'AdvertisingCondition' => [
                'Shimoning\ColorMeShopApi\Constants\AdvertisingCondition',
                'Shimoning\ColorMeShopApi\Constants\Product\Advertising\Condition',
            ],
            'AdvertisingGender' => [
                'Shimoning\ColorMeShopApi\Constants\AdvertisingGender',
                'Shimoning\ColorMeShopApi\Constants\Product\Advertising\Gender',
            ],
            'CategoryDisplayState' => [
                'Shimoning\ColorMeShopApi\Constants\CategoryDisplayState',
                'Shimoning\ColorMeShopApi\Constants\Product\Category\DisplayState',
            ],
            'GroupDisplayState' => [
                'Shimoning\ColorMeShopApi\Constants\GroupDisplayState',
                'Shimoning\ColorMeShopApi\Constants\Product\Group\DisplayState',
            ],
            'PickupType' => [
                'Shimoning\ColorMeShopApi\Constants\PickupType',
                'Shimoning\ColorMeShopApi\Constants\Product\Pickup\Type',
            ],
            'DeliveryChargeFreeType' => [
                'Shimoning\ColorMeShopApi\Constants\DeliveryChargeFreeType',
                'Shimoning\ColorMeShopApi\Constants\Delivery\ChargeFreeType',
            ],
            'DeliveryChargeType' => [
                'Shimoning\ColorMeShopApi\Constants\DeliveryChargeType',
                'Shimoning\ColorMeShopApi\Constants\Delivery\ChargeType',
            ],
            'DeliveryMethodType' => [
                'Shimoning\ColorMeShopApi\Constants\DeliveryMethodType',
                'Shimoning\ColorMeShopApi\Constants\Delivery\MethodType',
            ],
            'DisplayState' => [
                'Shimoning\ColorMeShopApi\Constants\DisplayState',
                'Shimoning\ColorMeShopApi\Constants\Delivery\DisplayState',
            ],
            'ContractPlan' => [
                'Shimoning\ColorMeShopApi\Constants\ContractPlan',
                'Shimoning\ColorMeShopApi\Constants\Shop\ContractPlan',
            ],
            'DomainPlan' => [
                'Shimoning\ColorMeShopApi\Constants\DomainPlan',
                'Shimoning\ColorMeShopApi\Constants\Shop\DomainPlan',
            ],
            'OpenState' => [
                'Shimoning\ColorMeShopApi\Constants\OpenState',
                'Shimoning\ColorMeShopApi\Constants\Shop\OpenState',
            ],
            'ShopState' => [
                'Shimoning\ColorMeShopApi\Constants\ShopState',
                'Shimoning\ColorMeShopApi\Constants\Shop\State',
            ],
            'TaxRoundingMethod' => [
                'Shimoning\ColorMeShopApi\Constants\TaxRoundingMethod',
                'Shimoning\ColorMeShopApi\Constants\Shop\TaxRoundingMethod',
            ],
            'TaxType' => [
                'Shimoning\ColorMeShopApi\Constants\TaxType',
                'Shimoning\ColorMeShopApi\Constants\Shop\TaxType',
            ],
            'KouzaType' => [
                'Shimoning\ColorMeShopApi\Constants\KouzaType',
                'Shimoning\ColorMeShopApi\Constants\Payment\KouzaType',
            ],
            'PaymentType' => [
                'Shimoning\ColorMeShopApi\Constants\PaymentType',
                'Shimoning\ColorMeShopApi\Constants\Payment\Type',
            ],
            'MailState' => [
                'Shimoning\ColorMeShopApi\Constants\MailState',
                'Shimoning\ColorMeShopApi\Constants\Sale\MailState',
            ],
            'PointState' => [
                'Shimoning\ColorMeShopApi\Constants\PointState',
                'Shimoning\ColorMeShopApi\Constants\Sale\PointState',
            ],
            'MailType' => [
                'Shimoning\ColorMeShopApi\Constants\MailType',
                'Shimoning\ColorMeShopApi\Constants\Sale\MailType',
            ],
            'ExternalAccountProvider' => [
                'Shimoning\ColorMeShopApi\Constants\ExternalAccountProvider',
                'Shimoning\ColorMeShopApi\Constants\Customer\ExternalAccountProvider',
            ],
            'AuthRedirectUri' => [
                'Shimoning\ColorMeShopApi\Constants\AuthRedirectUri',
                'Shimoning\ColorMeShopApi\Constants\OAuth\AuthRedirectUri',
            ],
            'AuthScope' => [
                'Shimoning\ColorMeShopApi\Constants\AuthScope',
                'Shimoning\ColorMeShopApi\Constants\OAuth\AuthScope',
            ],
        ];
    }

    #[DataProvider('unreleasedIntermediateNameProvider')]
    public function test_未リリースの途中名は定義されない(string $intermediate): void
    {
        $this->assertFalse(\class_exists($intermediate));
        $this->assertFalse(\enum_exists($intermediate));
    }

    /** @return array<string, array{string}> */
    public static function unreleasedIntermediateNameProvider(): array
    {
        return [
            'Product\\ProductDisplayState' => ['Shimoning\ColorMeShopApi\Constants\Product\ProductDisplayState'],
            'Product\\Advertising\\AdvertisingCondition' => ['Shimoning\ColorMeShopApi\Constants\Product\Advertising\AdvertisingCondition'],
            'Product\\Advertising\\AdvertisingGender' => ['Shimoning\ColorMeShopApi\Constants\Product\Advertising\AdvertisingGender'],
            'Product\\Category\\CategoryDisplayState' => ['Shimoning\ColorMeShopApi\Constants\Product\Category\CategoryDisplayState'],
            'Product\\Group\\GroupDisplayState' => ['Shimoning\ColorMeShopApi\Constants\Product\Group\GroupDisplayState'],
            'Product\\Pickup\\PickupType' => ['Shimoning\ColorMeShopApi\Constants\Product\Pickup\PickupType'],
            'Delivery\\DeliveryChargeFreeType' => ['Shimoning\ColorMeShopApi\Constants\Delivery\DeliveryChargeFreeType'],
            'Delivery\\DeliveryChargeType' => ['Shimoning\ColorMeShopApi\Constants\Delivery\DeliveryChargeType'],
            'Delivery\\DeliveryMethodType' => ['Shimoning\ColorMeShopApi\Constants\Delivery\DeliveryMethodType'],
            'Shop\\ShopState' => ['Shimoning\ColorMeShopApi\Constants\Shop\ShopState'],
            'Payment\\PaymentType' => ['Shimoning\ColorMeShopApi\Constants\Payment\PaymentType'],
        ];
    }
}
