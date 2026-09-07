<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * フィクスチャ読み込みなど、テスト共通のヘルパを提供する基底クラス。
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * tests/Fixtures 配下の JSON を文字列として読み込む
     */
    protected static function fixture(string $name): string
    {
        $path = __DIR__ . '/Fixtures/' . $name;
        if (! \is_file($path)) {
            throw new \RuntimeException('フィクスチャが見つかりません: ' . $path);
        }
        return \file_get_contents($path);
    }

    /**
     * tests/Fixtures 配下の JSON を連想配列として読み込む
     */
    protected static function fixtureArray(string $name): array
    {
        return \json_decode(static::fixture($name), true, 512, \JSON_THROW_ON_ERROR);
    }
}
