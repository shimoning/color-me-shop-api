<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductUpdateInputTest extends TestCase
{
    public function test_更新の全フィールドをproductボディ形式へ変換する(): void
    {
        $input = new ProductUpdateInput([
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

    public function test_stocksはincrementオブジェクトも受け付ける(): void
    {
        $input = new ProductUpdateInput(['stocks' => ['increment' => -1]]);

        $this->assertSame(['stocks' => ['increment' => -1]], $input->toArrayRecursive());
    }

    public function test_stocksとvariantsは構築済みEntityも受け付ける(): void
    {
        $stocks = new StocksIncrementInput(['increment' => 5]);
        $variant = new VariantInput(['option1_value' => 'S', 'stocks' => 3]);
        $input = new ProductUpdateInput(['stocks' => $stocks, 'variants' => [$variant]]);

        $this->assertSame([
            'stocks' => ['increment' => 5],
            'variants' => [['option1_value' => 'S', 'stocks' => 3]],
        ], $input->toArrayRecursive());
    }

    public function test_group_idsの不正要素は共通scalar配列メッセージを返す(): void
    {
        try {
            new ProductUpdateInput(['group_ids' => [301, '302']]);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ProductUpdateInput::class . " の API フィールド『group_ids』が不正です。"
                . '配列要素を int に変換できませんでした。原因: 配列要素の型が不正です。',
                $exception->getMessage(),
            );
            $this->assertSame('配列要素が int ではありません。', $exception->getPrevious()?->getMessage());
            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    public function test_stocksの不正型は新しいEntity名を含む完全なメッセージを返す(): void
    {
        try {
            new ProductUpdateInput(['stocks' => '10']);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ProductUpdateInput::class . " の API フィールド『stocks』が不正です。"
                . StocksIncrementInput::class . '|int を期待しましたが string でした。',
                $exception->getMessage(),
            );
            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    public function test_variantsの不正要素は新しいEntity名を含む完全なメッセージを返す(): void
    {
        try {
            new ProductUpdateInput(['variants' => [null]]);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ProductUpdateInput::class . " の API フィールド『variants』が不正です。"
                . '配列要素を ' . VariantInput::class
                . ' に変換できませんでした。原因: 配列要素の型が不正です。',
                $exception->getMessage(),
            );
            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_更新専用フィールドの不正な型を拒否する(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new ProductUpdateInput([$field => $value]);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFieldProvider(): array
    {
        return [
            'stocks string' => ['stocks', '10'],
            'stocks bool' => ['stocks', true],
            'stocks float' => ['stocks', 1.5],
            'group_ids scalar' => ['group_ids', 301],
            'variants scalar' => ['variants', 'S'],
        ];
    }

    #[DataProvider('validNestedShapeProvider')]
    public function test_ネストした入力値の正しい形状を受け付ける(string $field, mixed $value): void
    {
        $this->assertSame([$field => $value], (new ProductUpdateInput([$field => $value]))->toArrayRecursive());
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
        new ProductUpdateInput([$field => $value]);
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
            'variants non sequential list' => ['variants', [1 => ['option1_value' => 'S']]],
            'variants mixed keys' => [
                'variants',
                [0 => ['option1_value' => 'S'], 'second' => ['option1_value' => 'M']],
            ],
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
