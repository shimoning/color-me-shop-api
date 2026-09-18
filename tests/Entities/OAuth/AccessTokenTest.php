<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\OAuth;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;

class AccessTokenTest extends TestCase
{
    public function test_既存と追加のスコープをすべて解析する(): void
    {
        $values = [
            'read_products',
            'write_products',
            'read_sales',
            'write_sales',
            'read_shop_coupons',
            'write_shop_coupons',
            'read_templates',
            'write_templates',
            'read_analytics',
        ];

        $token = new AccessToken(['scope' => \implode(' ', $values)]);

        $this->assertSame(
            $values,
            \array_map(fn($scope) => $scope->value, $token->getScopes()),
        );
    }
}
