<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Constants\Product\Group;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\Product\Category\DisplayState as CategoryDisplayState;
use Shimoning\ColorMeShopApi\Constants\Product\Group\DisplayState;

class DisplayStateTest extends TestCase
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
            \array_map(static fn(DisplayState $case): string => $case->value, DisplayState::cases()),
        );
        $this->assertSame(DisplayState::SHOWING_FOR_MEMBERS, DisplayState::tryFrom('showing_for_members'));
        $this->assertSame(DisplayState::SALE_FOR_MEMBERS, DisplayState::tryFrom('sale_for_members'));
    }

    /**
     * 既存の Category\DisplayState::MEMBER_ONLY と同じ case 名にする (backing value も同じ)。
     */
    public function test_会員限定のcase名はCategoryのDisplayStateと揃える(): void
    {
        $this->assertSame(CategoryDisplayState::MEMBER_ONLY->name, DisplayState::MEMBER_ONLY->name);
        $this->assertSame(CategoryDisplayState::MEMBER_ONLY->value, DisplayState::MEMBER_ONLY->value);
    }

    public function test_日本語名を返す(): void
    {
        $this->assertSame('掲載状態', DisplayState::SHOWING->name());
        $this->assertSame('非掲載状態', DisplayState::HIDDEN->name());
        $this->assertSame('会員にのみ掲載', DisplayState::MEMBER_ONLY->name());
        $this->assertSame('会員にのみ掲載', DisplayState::SHOWING_FOR_MEMBERS->name());
        $this->assertSame('会員にのみ販売', DisplayState::SALE_FOR_MEMBERS->name());
    }
}
