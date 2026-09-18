<?php

namespace Shimoning\ColorMeShopApi\Tests;

class ClientScriptTest extends TestCase
{
    public function test_サンプルCLIは従来の5スコープだけを要求する(): void
    {
        $script = \file_get_contents(__DIR__ . '/../client');
        $this->assertIsString($script);

        $this->assertSame(
            1,
            \preg_match('/\$oAuthScopes\s*=\s*new Values\\\\Scopes\(\s*\[(.*?)\]\s*\);/s', $script, $matches),
            'サンプル CLI の OAuth scope は明示リストで指定する',
        );

        \preg_match_all('/Constants\\\\AuthScope::([A-Z_]+)/', $matches[1], $scopeCases);
        $this->assertSame(
            ['READ_PRODUCTS', 'WRITE_PRODUCTS', 'READ_SALES', 'WRITE_SALES', 'READ_SHOP_COUPONS'],
            $scopeCases[1],
        );
        $this->assertSame('', \preg_replace('/Constants\\\\AuthScope::[A-Z_]+|[\s,]/', '', $matches[1]));
    }
}
