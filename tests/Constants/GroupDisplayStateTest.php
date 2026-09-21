<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;

class GroupDisplayStateTest extends TestCase
{
    /**
     * 実 API の観測 (2026-09-21) でグループの display_state は showing / hidden / members_only の 3 値だった。
     * productGroup response の OpenAPI 定義にある showing_for_members / sale_for_members は含めない。
     */
    public function test_実測した3値だけを持つ(): void
    {
        $this->assertSame(
            ['showing', 'hidden', 'members_only'],
            \array_map(static fn(GroupDisplayState $case): string => $case->value, GroupDisplayState::cases()),
        );
        $this->assertNull(GroupDisplayState::tryFrom('showing_for_members'));
        $this->assertNull(GroupDisplayState::tryFrom('sale_for_members'));
    }

    public function test_日本語名を返す(): void
    {
        $this->assertSame('掲載状態', GroupDisplayState::SHOWING->name());
        $this->assertSame('非掲載状態', GroupDisplayState::HIDDEN->name());
        $this->assertSame('会員にのみ掲載', GroupDisplayState::MEMBERS_ONLY->name());
    }
}
