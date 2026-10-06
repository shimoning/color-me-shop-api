<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryIds;
use Shimoning\ColorMeShopApi\Entities\Product\Image as EmbeddedImage;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Option as ProductOption;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\Value;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\Pickup;
use Shimoning\ColorMeShopApi\Entities\Product\Product;
use Shimoning\ColorMeShopApi\Entities\Product\Image\Image as ProductImageEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\Option as ProductVariantOption;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ReadEntitiesTest extends TestCase
{
    public function test_商品全フィールドとネストを取得できる(): void
    {
        $data = self::fixtureArray('products_read.json')['product'];
        $product = new Product($data);

        $this->assertSame(49, count($product->toArray()));
        $this->assertSame(101, $product->getId());
        $this->assertSame(ProductDisplayState::SHOWING, $product->getDisplayState());
        $this->assertSame(501, $product->getCategory()->getIdBig());
        $this->assertSame(0, $product->getCategory()->getIdSmall());
        $this->assertSame([], $product->getGroupIds());
        $this->assertSame([], $product->getUnavailablePaymentIds());
        $this->assertSame([], $product->getUnavailableDeliveryIds());
        $this->assertInstanceOf(EmbeddedImage::class, $product->getImages()[0]);
        $this->assertInstanceOf(ProductOption::class, $product->getOptions()[0]);
        $this->assertSame(['赤', '青'], $product->getOptions()[0]->getValues());
        $this->assertInstanceOf(Variant::class, $product->getVariants()[0]);
        $this->assertNull($product->getVariants()[0]->getOption2());
        $this->assertInstanceOf(Pickup::class, $product->getPickups()[0]);
        $this->assertFalse($product->getDigitalContent());
        $this->assertTrue($product->getUnlisted());
        $this->assertArrayNotHasKey('digital_conent', $product->toArray());
        $this->assertInstanceOf(DateTimeImmutable::class, $product->getMakeDate());
        foreach (array_keys($data) as $field) {
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            $this->assertTrue(method_exists($product, $getter), $field);
            $product->$getter();
        }
    }

    public function test_画像専用応答は商品内画像と別構造である(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $embedded = new EmbeddedImage($fixture['product']['images'][0]);
        $dedicated = new ProductImageEntity($fixture['product_image']);

        $this->assertSame('https://example.invalid/item-extra.jpg', $embedded->getSrc());
        $this->assertFalse($embedded->getMobile());
        $this->assertSame('https://example.invalid/item-main.jpg', $dedicated->getUrl());
        $this->assertSame(['position', 'url'], array_keys($dedicated->toArray()));
    }

    public function test_独立オプション値と広告を取得できる(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $optionValue = new Value($fixture['option_value']);
        $advertising = new Advertising($fixture['advertising']);

        $this->assertSame('赤', $optionValue->getName());
        $this->assertSame(101, $advertising->getProductId());
        $this->assertSame([], $advertising->getColors());
    }

    public function test_必須フィールド欠損とnullableなnullを区別する(): void
    {
        $product = new Product(['category' => ['id_big' => 0, 'id_small' => 0], 'price' => null]);
        $this->assertNull($product->getPrice());
        $this->expectException(MissingFieldException::class);
        $product->getId();
    }

    public function test_オプションのmake_dateは実測どおり明示的なnullを許容する(): void
    {
        $data = self::fixtureArray('products_read.json')['product'];
        $optionData = $data['options'][0];
        $optionData['make_date'] = null;

        $option = new ProductOption($optionData);
        $this->assertNull($option->getMakeDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $option->getUpdateDate());

        $data['options'] = [$optionData];
        $product = new Product($data);
        $this->assertNull($product->getOptions()[0]->getMakeDate());
        $this->assertSame(['赤', '青'], $product->getOptions()[0]->getValues());
    }

    public function test_オプションのmake_dateが整数ならDateTimeImmutableになる(): void
    {
        $option = new ProductOption(self::fixtureArray('products_read.json')['product']['options'][0]);

        $this->assertInstanceOf(DateTimeImmutable::class, $option->getMakeDate());
        $this->assertSame(1700000000, $option->getMakeDate()->getTimestamp());
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_不正型は固有例外になる(string $class, string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new $class([$field => $value]);
    }

    public static function invalidFieldProvider(): array
    {
        return [
            [Product::class, 'digital_content', 1],
            [Product::class, 'unlisted', 'true'],
            [Product::class, 'category', null],
            [Product::class, 'images', [null]],
            [Variant::class, 'option2', false],
            [ProductOption::class, 'values', [12]],
            [ProductImageEntity::class, 'url', 12],
            [Advertising::class, 'condition', 'unknown'],
            [CategoryIds::class, 'id_big', '0'],
            [EmbeddedImage::class, 'mobile', 0],
            [Value::class, 'name', 1],
            [Pickup::class, 'pickup_type', null],
            [ProductVariantOption::class, 'id', '1'],
        ];
    }
}
