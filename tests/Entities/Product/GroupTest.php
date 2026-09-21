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

        $this->assertSame(GroupDisplayState::MEMBERS_ONLY, $group->getDisplayState());
    }

    /**
     * OpenAPI の productGroup response には showing_for_members / sale_for_members が列挙されているが、
     * 実 API では書き込みで拒否され読み取りでも観測されなかったため、未知値として扱う。
     */
    public function test_商品の会員向け表示状態は不正な値として拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('display_state');
        $this->makeGroup(['display_state' => 'showing_for_members']);
    }
}
