<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductCreateInputTest extends TestCase
{
    /** @return array<string, array{class-string<ProductCreateInput|ProductUpdateInput>}> */
    public static function productInputProvider(): array
    {
        return [
            'create' => [ProductCreateInput::class],
            'update' => [ProductUpdateInput::class],
        ];
    }

    public function test_作成の全フィールドをproductボディ形式へ変換する(): void
    {
        $input = new ProductCreateInput([
            'name' => 'Tシャツ',
            'price' => 1600,
            'category_id_big' => 1139,
            'cost' => 800,
            'sales_price' => 1500,
            'members_price' => 1400,
            'model_number' => 'T-223',
            'expl' => '説明',
            'simple_expl' => '簡易説明',
            'smartphone_expl' => 'スマホ説明',
            'display_state' => 'hidden',
            'stock_managed' => true,
            'tax_reduced' => false,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame([
            'name' => 'Tシャツ',
            'price' => 1600,
            'category_id_big' => 1139,
            'cost' => 800,
            'sales_price' => 1500,
            'members_price' => 1400,
            'model_number' => 'T-223',
            'expl' => '説明',
            'simple_expl' => '簡易説明',
            'smartphone_expl' => 'スマホ説明',
            'display_state' => 'hidden',
            'stock_managed' => true,
            'tax_reduced' => false,
        ], $input->toArrayRecursive());
    }

    public function test_更新専用フィールドは未宣言キーとして無視する(): void
    {
        $input = new ProductCreateInput([
            'category_id_small' => 2,
            'stocks' => 10,
            'group_ids' => [301, 302],
            'variants' => [['option1_value' => 'S', 'stocks' => 3]],
        ]);

        $this->assertSame([], $input->toArrayRecursive());
    }

    /** @param class-string<ProductCreateInput|ProductUpdateInput> $class */
    #[DataProvider('productInputProvider')]
    public function test_未指定のフィールドは送信しない(string $class): void
    {
        $this->assertSame([], (new $class([]))->toArrayRecursive());
        $this->assertSame(['name' => '名前だけ'], (new $class(['name' => '名前だけ']))->toArrayRecursive());
    }

    /** @param class-string<ProductCreateInput|ProductUpdateInput> $class */
    #[DataProvider('productInputProvider')]
    public function test_明示したnullも送信する(string $class): void
    {
        $input = new $class(['sales_price' => null, 'name' => '商品']);

        $this->assertSame(['name' => '商品', 'sales_price' => null], $input->toArrayRecursive());
        $this->assertSame(['name' => '商品', 'sales_price' => null], $input->toArrayRecursive(false));
    }

    /** @param class-string<ProductCreateInput|ProductUpdateInput> $class */
    #[DataProvider('productInputProvider')]
    public function test_display_stateは実測で受理された4値だけを受け付ける(string $class): void
    {
        foreach (['showing', 'hidden', 'showing_for_members', 'sale_for_members'] as $state) {
            $this->assertSame(['display_state' => $state], (new $class(['display_state' => $state]))->toArrayRecursive());
        }

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('display_state');
        new $class(['display_state' => 'members_only']);
    }

    /** @param class-string<ProductCreateInput|ProductUpdateInput> $class */
    #[DataProvider('productInputProvider')]
    public function test_display_stateはenumインスタンスでも指定できバッキング値で送信する(string $class): void
    {
        $input = new $class(['display_state' => ProductDisplayState::HIDDEN]);

        $this->assertSame(ProductDisplayState::HIDDEN, $input->toArray()['display_state']);
        $this->assertSame(['display_state' => 'hidden'], $input->toArrayRecursive());
    }

    /** @param class-string<ProductCreateInput|ProductUpdateInput> $class */
    #[DataProvider('productInputProvider')]
    public function test_書き込みできないunlistedは入力フィールドに持たない(string $class): void
    {
        $input = new $class(['unlisted' => true]);

        $this->assertArrayNotHasKey('unlisted', $input->toArray());
        $this->assertSame([], $input->toArrayRecursive());
    }

    /** @param class-string<ProductCreateInput|ProductUpdateInput> $class */
    #[DataProvider('invalidSharedFieldProvider')]
    public function test_共通フィールドの不正な型を拒否する(string $class, string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new $class([$field => $value]);
    }

    /** @return array<string, array{class-string<ProductCreateInput|ProductUpdateInput>, string, mixed}> */
    public static function invalidSharedFieldProvider(): array
    {
        $cases = [];
        foreach (self::productInputProvider() as $operation => [$class]) {
            $cases[$operation . ': price string'] = [$class, 'price', '1600'];
            $cases[$operation . ': stock_managed int'] = [$class, 'stock_managed', 1];
        }
        return $cases;
    }
}
