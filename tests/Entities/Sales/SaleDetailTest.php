<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDetail;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleDetailTest extends TestCase
{
    public function test_商品名が欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $detail = new SaleDetail([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleDetail::class . ' の API フィールド『product_name』が欠損しています。',
        );

        $detail->getProductName();
    }

    public function test_nullableフィールドの欠損は従来どおりnullになる(): void
    {
        $detail = new SaleDetail([]);

        $this->assertNull($detail->getSaleDeliveryId());
        $this->assertNull($detail->getProductCost());
        $this->assertNull($detail->getUnit());
    }

    public function test_完全な受注明細を従来どおり取得できる(): void
    {
        $detail = new SaleDetail([
            'id' => 11,
            'sale_id' => 1001,
            'account_id' => 'my-shop',
            'product_id' => 501,
            'sale_delivery_id' => 21,
            'option1_value' => '赤',
            'option2_value' => 'L',
            'option1_index' => 1,
            'option2_index' => 2,
            'product_model_number' => 'MODEL-1',
            'product_name' => '商品名',
            'pristine_product_full_name' => '商品名 赤 L',
            'product_cost' => 500,
            'product_image_url' => 'https://example.com/image.jpg',
            'product_thumbnail_image_url' => 'https://example.com/thumbnail.jpg',
            'product_mobile_image_url' => 'https://example.com/mobile.jpg',
            'price' => 1000,
            'price_with_tax' => 1100,
            'product_num' => 2,
            'unit' => '個',
            'subtotal_price' => 2200,
        ]);

        $this->assertSame(11, $detail->getId());
        $this->assertSame(1001, $detail->getSaleId());
        $this->assertSame('my-shop', $detail->getAccountId());
        $this->assertSame(501, $detail->getProductId());
        $this->assertSame('商品名', $detail->getProductName());
        $this->assertSame('商品名 赤 L', $detail->getPristineProductFullName());
        $this->assertSame(1000, $detail->getPrice());
        $this->assertSame(1100, $detail->getPriceWithTax());
        $this->assertSame(2, $detail->getProductNum());
        $this->assertSame(2200, $detail->getSubtotalPrice());
    }
}
