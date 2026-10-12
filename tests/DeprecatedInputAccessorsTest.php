<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput;

class DeprecatedInputAccessorsTest extends TestCase
{
    /**
     * @param class-string $class
     */
    #[DataProvider('deprecatedMethodProvider')]
    public function test_入力Entityのアクセサと変換メソッドは代替方法を示して非推奨である(
        string $class,
        string $method,
        string $alternative,
    ): void {
        $document = (new ReflectionMethod($class, $method))->getDocComment();

        $this->assertIsString($document);
        $this->assertStringContainsString(
            '@deprecated 0.31.0 ' . $alternative . '。',
            $document,
        );
    }

    /**
     * @return array<string, array{class-string, string, string}>
     */
    public static function deprecatedMethodProvider(): array
    {
        $saleGetters = [
            'SaleUpdateInput::isPaid' => 'isPaid',
            'SaleUpdateInput::getPointState' => 'getPointState',
            'SaleUpdateInput::getSaleDeliveries' => 'getSaleDeliveries',
        ];
        $saleSetters = [
            'SaleUpdateInput::setPaid' => 'setPaid',
            'SaleUpdateInput::setPointState' => 'setPointState',
            'SaleUpdateInput::setSaleDeliveries' => 'setSaleDeliveries',
        ];
        $deliverySetters = [
            'setName',
            'setFurigana',
            'setPostal',
            'setPrefId',
            'setPrefName',
            'setAddress1',
            'setAddress2',
            'setTel',
            'setPreferredDate',
            'setPreferredPeriod',
            'setSlipNumber',
            'setNoshiText',
            'setNoshiCharge',
            'setCardName',
            'setCardText',
            'setCardCharge',
            'setWrappingName',
            'setWrappingCharge',
            'setDeliveryCharge',
            'setTotalCharge',
            'setTrackingUrl',
            'setMemo',
            'setDelivered',
        ];

        $cases = [
            'SaleUpdateInput::convert' => [
                SaleUpdateInput::class,
                'convert',
                '送りたい項目だけをコンストラクタの配列で渡すこと',
            ],
        ];
        foreach ($saleGetters as $name => $method) {
            $cases[$name] = [
                SaleUpdateInput::class,
                $method,
                '送信内容は toArrayRecursive() で確かめること',
            ];
        }
        foreach ($saleSetters as $name => $method) {
            $cases[$name] = [
                SaleUpdateInput::class,
                $method,
                'コンストラクタの配列で指定すること',
            ];
        }
        foreach ($deliverySetters as $method) {
            $cases['DeliveryUpdateInput::' . $method] = [
                DeliveryUpdateInput::class,
                $method,
                'コンストラクタの配列で指定すること',
            ];
        }
        foreach ([
            'Product\\SearchParameters' => ProductSearchParameters::class,
            'Product\\Stock\\SearchParameters' => StockSearchParameters::class,
            'Product\\Advertising\\SearchParameters' => AdvertisingSearchParameters::class,
        ] as $name => $class) {
            $cases[$name . '::getDisplayState'] = [
                $class,
                'getDisplayState',
                '送信内容は toArrayRecursive() で確かめること',
            ];
        }

        return $cases;
    }
}
