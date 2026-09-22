<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;

class GroupDisplayStateTest extends TestCase
{
    /**
     * 実 API の観測 (2026-09-21) でグループの display_state は showing / hidden / members_only の 3 値だった。
     * productGroup response の OpenAPI 定義にある showing_for_members / sale_for_members は、管理画面等で
     * 設定された既存グループが応答で返す可能性を否定できないため、応答の受理のみを目的として含める。
     */
    public function test_実測した3値と公式response定義の2値を持つ(): void
    {
        $this->assertSame(
            ['showing', 'hidden', 'members_only', 'showing_for_members', 'sale_for_members'],
            \array_map(static fn(GroupDisplayState $case): string => $case->value, GroupDisplayState::cases()),
        );
        $this->assertSame(GroupDisplayState::SHOWING_FOR_MEMBERS, GroupDisplayState::tryFrom('showing_for_members'));
        $this->assertSame(GroupDisplayState::SALE_FOR_MEMBERS, GroupDisplayState::tryFrom('sale_for_members'));
    }

    /**
     * 既存の CategoryDisplayState::MEMBER_ONLY と同じ case 名にする (backing value も同じ)。
     */
    public function test_会員限定のcase名はCategoryDisplayStateと揃える(): void
    {
        $this->assertSame(CategoryDisplayState::MEMBER_ONLY->name, GroupDisplayState::MEMBER_ONLY->name);
        $this->assertSame(CategoryDisplayState::MEMBER_ONLY->value, GroupDisplayState::MEMBER_ONLY->value);
    }

    public function test_日本語名を返す(): void
    {
        $this->assertSame('掲載状態', GroupDisplayState::SHOWING->name());
        $this->assertSame('非掲載状態', GroupDisplayState::HIDDEN->name());
        $this->assertSame('会員にのみ掲載', GroupDisplayState::MEMBER_ONLY->name());
        $this->assertSame('会員にのみ掲載', GroupDisplayState::SHOWING_FOR_MEMBERS->name());
        $this->assertSame('会員にのみ販売', GroupDisplayState::SALE_FOR_MEMBERS->name());
    }
}
