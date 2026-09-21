<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTagInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class GroupInputTest extends TestCase
{
    public function test_全フィールドをgroupボディ形式へ変換する(): void
    {
        $input = new GroupInput([
            'name' => '夏物',
            'expl' => '夏物の衣類',
            'display_state' => 'hidden',
            'parent_group_id' => 1,
            'meta_tag' => ['title' => '夏物特集', 'keywords' => '夏物', 'description' => '夏物の一覧'],
        ]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(ProductDisplayState::HIDDEN, $input->toArray()['display_state']);
        $this->assertInstanceOf(MetaTagInput::class, $input->toArray()['meta_tag']);
        $this->assertSame([
            'name' => '夏物',
            'expl' => '夏物の衣類',
            'display_state' => 'hidden',
            'parent_group_id' => 1,
            'meta_tag' => ['title' => '夏物特集', 'keywords' => '夏物', 'description' => '夏物の一覧'],
        ], $input->toArrayRecursive());
    }

    public function test_未指定は送信せず明示したnullは送信する(): void
    {
        $this->assertSame([], (new GroupInput([]))->toArrayRecursive());
        $this->assertSame(
            ['name' => '夏物', 'expl' => null, 'parent_group_id' => null, 'meta_tag' => null],
            (new GroupInput(['name' => '夏物', 'expl' => null, 'parent_group_id' => null, 'meta_tag' => null]))
                ->toArrayRecursive(),
        );
    }

    public function test_meta_tagの中でも未指定は送信せず明示したnullは送信する(): void
    {
        $input = new GroupInput(['meta_tag' => ['title' => null]]);

        $this->assertSame(['meta_tag' => ['title' => null]], $input->toArrayRecursive());
    }

    public function test_表示状態はenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new GroupInput(['display_state' => ProductDisplayState::SHOWING_FOR_MEMBERS]);

        $this->assertSame(['display_state' => 'showing_for_members'], $input->toArrayRecursive());
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_不正な値と形状を構築時に拒否する(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new GroupInput([$field => $value]);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFieldProvider(): array
    {
        return [
            'name int' => ['name', 1],
            'expl int' => ['expl', 1],
            'parent_group_id string' => ['parent_group_id', '1'],
            'display_state members_only (request 定義の値だが応答 enum にない)' => ['display_state', 'members_only'],
            'display_state unknown' => ['display_state', 'unknown'],
            'display_state other enum' => ['display_state', CategoryDisplayState::HIDDEN],
            'meta_tag string' => ['meta_tag', 'title'],
            'meta_tag empty array (JSON で [] になる)' => ['meta_tag', []],
            'meta_tag list' => ['meta_tag', ['title']],
            'meta_tag unknown keys only' => ['meta_tag', ['titel' => 'x']],
            'meta_tag title int' => ['meta_tag', ['title' => 1]],
        ];
    }
}
