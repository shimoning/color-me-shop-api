<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTagInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

/**
 * 大カテゴリー (CategoryInput) と小カテゴリー (CategoryChildInput) の入力は
 * 公式 OpenAPI で同じ `category` object のため、同じ検証をどちらにも適用する。
 */
class CategoryInputTest extends TestCase
{
    /** @return array<string, array{class-string<CategoryInput|CategoryChildInput>}> */
    public static function classProvider(): array
    {
        return [
            'CategoryInput' => [CategoryInput::class],
            'CategoryChildInput' => [CategoryChildInput::class],
        ];
    }

    /** @param class-string<CategoryInput|CategoryChildInput> $class */
    #[DataProvider('classProvider')]
    public function test_全フィールドをcategoryボディ形式へ変換する(string $class): void
    {
        $input = new $class([
            'name' => 'Tシャツ',
            'expl' => '高品質Tシャツ',
            'sort' => 1,
            'display_state' => 'members_only',
            'meta_tag' => ['title' => 'Tシャツ一覧', 'keywords' => 'Tシャツ', 'description' => 'Tシャツの一覧'],
        ]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(CategoryDisplayState::MEMBER_ONLY, $input->toArray()['display_state']);
        $this->assertInstanceOf(MetaTagInput::class, $input->toArray()['meta_tag']);
        $this->assertSame([
            'name' => 'Tシャツ',
            'expl' => '高品質Tシャツ',
            'sort' => 1,
            'display_state' => 'members_only',
            'meta_tag' => ['title' => 'Tシャツ一覧', 'keywords' => 'Tシャツ', 'description' => 'Tシャツの一覧'],
        ], $input->toArrayRecursive());
    }

    /** @param class-string<CategoryInput|CategoryChildInput> $class */
    #[DataProvider('classProvider')]
    public function test_未指定は送信せず明示したnullは送信する(string $class): void
    {
        $this->assertSame([], (new $class([]))->toArrayRecursive());
        $this->assertSame(
            ['name' => 'Tシャツ', 'expl' => null, 'sort' => null, 'meta_tag' => null],
            (new $class(['name' => 'Tシャツ', 'expl' => null, 'sort' => null, 'meta_tag' => null]))->toArrayRecursive(),
        );
        $this->assertSame(
            ['meta_tag' => ['description' => null]],
            (new $class(['meta_tag' => ['description' => null]]))->toArrayRecursive(),
        );
    }

    /** @param class-string<CategoryInput|CategoryChildInput> $class */
    #[DataProvider('classProvider')]
    public function test_表示状態はenumインスタンスでも指定できバッキング値で送信する(string $class): void
    {
        $input = new $class(['display_state' => CategoryDisplayState::HIDDEN]);

        $this->assertSame(['display_state' => 'hidden'], $input->toArrayRecursive());
    }

    public function test_大カテゴリー入力と小カテゴリー入力は互いに代入できない(): void
    {
        $this->assertNotInstanceOf(CategoryChildInput::class, new CategoryInput([]));
        $this->assertNotInstanceOf(CategoryInput::class, new CategoryChildInput([]));
    }

    /** @param class-string<CategoryInput|CategoryChildInput> $class */
    #[DataProvider('invalidFieldProvider')]
    public function test_不正な値と形状を構築時に拒否する(string $class, string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new $class([$field => $value]);
    }

    /** @return array<string, array{class-string<CategoryInput|CategoryChildInput>, string, mixed}> */
    public static function invalidFieldProvider(): array
    {
        $cases = [
            'name int' => ['name', 1],
            'expl int' => ['expl', 1],
            'sort string' => ['sort', '1'],
            'display_state showing_for_members (商品の値)' => ['display_state', 'showing_for_members'],
            'display_state unknown' => ['display_state', 'unknown'],
            'display_state other enum' => ['display_state', ProductDisplayState::HIDDEN],
            'meta_tag string' => ['meta_tag', 'title'],
            'meta_tag empty array (JSON で [] になる)' => ['meta_tag', []],
            'meta_tag list' => ['meta_tag', ['title']],
            'meta_tag unknown keys only' => ['meta_tag', ['titel' => 'x']],
            'meta_tag keywords int' => ['meta_tag', ['keywords' => 1]],
        ];

        $provided = [];
        foreach (self::classProvider() as $name => [$class]) {
            foreach ($cases as $label => [$field, $value]) {
                $provided[$name . ' ' . $label] = [$class, $field, $value];
            }
        }

        return $provided;
    }
}
