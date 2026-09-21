<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductInputTest extends TestCase
{
    public function test_作成と更新の全フィールドをproductボディ形式へ変換する(): void
    {
        $input = new ProductInput([
            'name' => 'Tシャツ',
            'price' => 1600,
            'category_id_big' => 1139,
            'category_id_small' => 2,
            'cost' => 800,
            'sales_price' => 1500,
            'members_price' => 1400,
            'model_number' => 'T-223',
            'expl' => '説明',
            'simple_expl' => '簡易説明',
            'smartphone_expl' => 'スマホ説明',
            'display_state' => 'hidden',
            'stock_managed' => true,
            'stocks' => 10,
            'group_ids' => [301, 302],
            'variants' => [
                ['option1_value' => 'S', 'option2_value' => '赤', 'stocks' => 3],
                ['option1_value' => 'M', 'option2_value' => '赤', 'stocks' => ['increment' => 2]],
            ],
            'tax_reduced' => false,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame([
            'name' => 'Tシャツ',
            'price' => 1600,
            'category_id_big' => 1139,
            'category_id_small' => 2,
            'cost' => 800,
            'sales_price' => 1500,
            'members_price' => 1400,
            'model_number' => 'T-223',
            'expl' => '説明',
            'simple_expl' => '簡易説明',
            'smartphone_expl' => 'スマホ説明',
            'display_state' => 'hidden',
            'stock_managed' => true,
            'stocks' => 10,
            'group_ids' => [301, 302],
            'variants' => [
                ['option1_value' => 'S', 'option2_value' => '赤', 'stocks' => 3],
                ['option1_value' => 'M', 'option2_value' => '赤', 'stocks' => ['increment' => 2]],
            ],
            'tax_reduced' => false,
        ], $input->toArrayRecursive());
    }

    public function test_未指定のフィールドは送信しない(): void
    {
        $this->assertSame([], (new ProductInput([]))->toArrayRecursive());
        $this->assertSame(['name' => '名前だけ'], (new ProductInput(['name' => '名前だけ']))->toArrayRecursive());
    }

    public function test_明示したnullはクリア要求として送信する(): void
    {
        $input = new ProductInput(['sales_price' => null, 'name' => '商品']);

        $this->assertSame(['name' => '商品', 'sales_price' => null], $input->toArrayRecursive());
        $this->assertSame(['name' => '商品', 'sales_price' => null], $input->toArrayRecursive(false));
    }

    public function test_stocksはincrementオブジェクトも受け付ける(): void
    {
        $input = new ProductInput(['stocks' => ['increment' => -1]]);

        $this->assertSame(['stocks' => ['increment' => -1]], $input->toArrayRecursive());
    }

    public function test_display_stateは実測で受理された4値だけを受け付ける(): void
    {
        foreach (['showing', 'hidden', 'showing_for_members', 'sale_for_members'] as $state) {
            $this->assertSame(['display_state' => $state], (new ProductInput(['display_state' => $state]))->toArrayRecursive());
        }

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('display_state');
        new ProductInput(['display_state' => 'members_only']);
    }

    public function test_display_stateはenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new ProductInput(['display_state' => ProductDisplayState::HIDDEN]);

        $this->assertSame(ProductDisplayState::HIDDEN, $input->toArray()['display_state']);
        $this->assertSame(['display_state' => 'hidden'], $input->toArrayRecursive());
    }

    public function test_書き込みできないunlistedは入力フィールドに持たない(): void
    {
        $input = new ProductInput(['unlisted' => true]);

        $this->assertArrayNotHasKey('unlisted', $input->toArray());
        $this->assertSame([], $input->toArrayRecursive());
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_不正な型を拒否する(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new ProductInput([$field => $value]);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFieldProvider(): array
    {
        return [
            'price string' => ['price', '1600'],
            'stock_managed int' => ['stock_managed', 1],
            'stocks string' => ['stocks', '10'],
            'group_ids scalar' => ['group_ids', 301],
            'variants scalar' => ['variants', 'S'],
        ];
    }

    #[DataProvider('validNestedShapeProvider')]
    public function test_ネストした入力値の正しい形状を受け付ける(string $field, mixed $value): void
    {
        $this->assertSame([$field => $value], (new ProductInput([$field => $value]))->toArrayRecursive());
    }

    /** @return array<string, array{string, mixed}> */
    public static function validNestedShapeProvider(): array
    {
        return [
            'group_ids empty' => ['group_ids', []],
            'group_ids int list' => ['group_ids', [301, 302]],
            'stocks int' => ['stocks', 0],
            'stocks increment' => ['stocks', ['increment' => -1]],
            'variants empty' => ['variants', []],
            'variants element without stocks' => ['variants', [['option1_value' => 'S', 'option2_value' => '赤']]],
            'variants element stocks int' => ['variants', [['option1_value' => 'S', 'stocks' => 3]]],
            'variants element stocks increment' => ['variants', [['option1_value' => 'S', 'stocks' => ['increment' => 2]]]],
        ];
    }

    #[DataProvider('invalidNestedShapeProvider')]
    public function test_ネストした入力値の要素型と形状が不正なら拒否する(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new ProductInput([$field => $value]);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidNestedShapeProvider(): array
    {
        return [
            'group_ids string element' => ['group_ids', [301, '302']],
            'group_ids null element' => ['group_ids', [null]],
            'group_ids hash' => ['group_ids', ['a' => 301]],
            'stocks empty array' => ['stocks', []],
            'stocks increment string' => ['stocks', ['increment' => '2']],
            'stocks increment null' => ['stocks', ['increment' => null]],
            'stocks unknown key' => ['stocks', ['incr' => 2]],
            'stocks extra key' => ['stocks', ['increment' => 2, 'x' => 1]],
            'stocks list' => ['stocks', [2]],
            'variants hash instead of list' => ['variants', ['option1_value' => 'S']],
            'variants scalar element' => ['variants', ['S']],
            'variants null element' => ['variants', [null]],
            'variants empty element' => ['variants', [[]]],
            'variants second element empty' => ['variants', [['option1_value' => 'S'], []]],
            'variants option1_value int' => ['variants', [['option1_value' => 1]]],
            'variants option2_value null' => ['variants', [['option2_value' => null]]],
            'variants stocks string' => ['variants', [['option1_value' => 'S', 'stocks' => '3']]],
            'variants stocks null' => ['variants', [['option1_value' => 'S', 'stocks' => null]]],
            'variants stocks increment string' => ['variants', [['option1_value' => 'S', 'stocks' => ['increment' => '2']]]],
            'variants unknown key' => ['variants', [['option1_value' => 'S', 'weight' => 1]]],
        ];
    }
}
