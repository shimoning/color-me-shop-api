<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
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
        $this->assertSame(GroupDisplayState::HIDDEN, $input->toArray()['display_state']);
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
        $input = new GroupInput(['display_state' => GroupDisplayState::MEMBER_ONLY]);

        $this->assertSame(['display_state' => 'members_only'], $input->toArrayRecursive());
    }

    /**
     * 実 API のグループ PUT は showing / hidden / members_only を受理する (2026-09-21 観測)。
     */
    public function test_会員限定の表示状態を文字列で指定できる(): void
    {
        $input = new GroupInput(['display_state' => 'members_only']);

        $this->assertSame(['display_state' => 'members_only'], $input->toArrayRecursive());
    }

    /**
     * 応答用の GroupDisplayState は 5 値だが、公式 OpenAPI の作成・更新 request と実 API の観測 (2026-09-21) は
     * showing / hidden / members_only の 3 値なので、送信前に残り 2 値を拒否する。
     */
    public function test_送信できる表示状態は3値に限定する(): void
    {
        $this->assertSame(
            [GroupDisplayState::SHOWING, GroupDisplayState::HIDDEN, GroupDisplayState::MEMBER_ONLY],
            GroupInput::WRITABLE_DISPLAY_STATES,
        );
        foreach (GroupInput::WRITABLE_DISPLAY_STATES as $state) {
            $this->assertSame(['display_state' => $state->value], (new GroupInput(['display_state' => $state]))->toArrayRecursive());
            $this->assertSame(['display_state' => $state->value], (new GroupInput(['display_state' => $state->value]))->toArrayRecursive());
        }
    }

    public function test_response専用の表示状態は構築時に3値を示すメッセージで拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage("'showing'|'hidden'|'members_only'");
        new GroupInput(['display_state' => GroupDisplayState::SALE_FOR_MEMBERS]);
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
            'display_state showing_for_members (response 専用の値。実 API の PUT は 422)' => ['display_state', 'showing_for_members'],
            'display_state sale_for_members (response 専用の値。実 API の PUT は 422)' => ['display_state', 'sale_for_members'],
            'display_state showing_for_members enum (response 専用の case)' => ['display_state', GroupDisplayState::SHOWING_FOR_MEMBERS],
            'display_state sale_for_members enum (response 専用の case)' => ['display_state', GroupDisplayState::SALE_FOR_MEMBERS],
            'display_state unknown' => ['display_state', 'unknown'],
            'display_state category enum' => ['display_state', CategoryDisplayState::HIDDEN],
            'display_state product enum' => ['display_state', ProductDisplayState::HIDDEN],
            'meta_tag string' => ['meta_tag', 'title'],
            'meta_tag empty array (JSON で [] になる)' => ['meta_tag', []],
            'meta_tag list' => ['meta_tag', ['title']],
            'meta_tag unknown keys only' => ['meta_tag', ['titel' => 'x']],
            'meta_tag unknown key beside a valid key (黙って捨てない)' => ['meta_tag', ['title' => 'x', 'titel' => 'typo']],
            'meta_tag valid keys and a list element' => ['meta_tag', ['title' => 'x', 'y']],
            'meta_tag title int' => ['meta_tag', ['title' => 1]],
        ];
    }
}
