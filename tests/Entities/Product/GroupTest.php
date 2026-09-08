<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;

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
        $this->assertSame(ProductDisplayState::SHOWING, $group->getDisplayState());
    }
}
