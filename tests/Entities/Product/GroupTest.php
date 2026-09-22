<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class GroupTest extends TestCase
{
    private function makeGroup(array $overrides = []): Group
    {
        return new Group($overrides + [
            'id' => 2,
            'account_id' => 'my-shop',
            'name' => 'セール',
            'display_state' => 'showing',
            'parent_group_id' => 1,
        ]);
    }

    public function test_親グループIDを取得する(): void
    {
        $this->assertSame(1, $this->makeGroup()->getParentGroupId());
    }

    /**
     * API 仕様に「親グループが存在しない場合は null になります」と明記されている。
     * プロパティが非 null 許容だと、トップレベルのグループが1つでもあるだけで
     * 商品グループ一覧の取得そのものが TypeError で落ちていた。
     */
    public function test_親グループがない場合はnullを返す(): void
    {
        $group = $this->makeGroup(['parent_group_id' => null]);

        $this->assertNull($group->getParentGroupId());
    }

    public function test_その他の項目も取得できる(): void
    {
        $group = $this->makeGroup();

        $this->assertSame(2, $group->getId());
        $this->assertSame('my-shop', $group->getAccountId());
        $this->assertSame('セール', $group->getName());
        $this->assertSame(GroupDisplayState::SHOWING, $group->getDisplayState());
    }

    /**
     * 実 API は members_only のグループを返す (2026-09-21 観測)。以前の ProductDisplayState (4値) では
     * members_only のグループが1件でもあると一覧・単体取得が InvalidFieldException で失敗していた。
     */
    public function test_会員限定のグループを読める(): void
    {
        $group = $this->makeGroup(['display_state' => 'members_only']);

        $this->assertSame(GroupDisplayState::MEMBER_ONLY, $group->getDisplayState());
    }

    /**
     * OpenAPI の productGroup response には showing_for_members / sale_for_members が列挙されている。
     * 実 API では PUT で 422 になり読み取りでも観測されなかったが、管理画面等で設定された既存グループが
     * 応答で返す可能性を否定できないため、応答では受理する (未知値で一覧全体が読めなくなるのを避ける)。
     */
    public function test_公式response定義の会員向け表示状態を読める(): void
    {
        $this->assertSame(
            GroupDisplayState::SHOWING_FOR_MEMBERS,
            $this->makeGroup(['display_state' => 'showing_for_members'])->getDisplayState(),
        );
        $this->assertSame(
            GroupDisplayState::SALE_FOR_MEMBERS,
            $this->makeGroup(['display_state' => 'sale_for_members'])->getDisplayState(),
        );
    }

    public function test_未知の表示状態は不正な値として拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('display_state');
        $this->makeGroup(['display_state' => 'unknown']);
    }
}
