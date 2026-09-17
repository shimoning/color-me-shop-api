<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Tests\Support\ExceptionCallSiteScanner;

class ExceptionCallSiteScannerTest extends TestCase
{
    public function test_別名と完全修飾名を解決しコメントと文字列を除外する(): void
    {
        $source = <<<'PHP'
<?php
namespace Example;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException as BadField;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
// BadField::for('ignored');
/** BadField::for('ignored'); */
$text = 'BadField::for("ignored")';
$interpolated = "BadField::for(ignored) {$text}";
$heredoc = <<<TEXT
BadField::for('ignored');
TEXT;
BadField::for('real');
new \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException('real');
new MissingPaginationException('real');
new MissingPaginationException;
PHP;

        self::assertSame([
            'src/Example.php:12' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::for', 'endLine' => 12],
            'src/Example.php:13' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException::__construct', 'endLine' => 13],
            'src/Example.php:14' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct', 'endLine' => 14],
            'src/Example.php:15' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct', 'endLine' => 15],
        ], ExceptionCallSiteScanner::scan($source, 'src/Example.php'));
    }

    public function test_同じ経路の別々の生成箇所を行番号で区別する(): void
    {
        $source = <<<'PHP'
<?php
namespace Shimoning\ColorMeShopApi\Exceptions;
MissingFieldException::for('first');

MissingFieldException::for('second');
PHP;

        self::assertSame([
            'src/Example.php:3' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::for', 'endLine' => 3],
            'src/Example.php:5' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::for', 'endLine' => 5],
        ], ExceptionCallSiteScanner::scan($source, 'src/Example.php'));
    }

    public function test_グループ化されたuseの別名を解決する(): void
    {
        $source = <<<'PHP'
<?php
namespace Example;
use Shimoning\ColorMeShopApi\Exceptions\{InvalidFieldException as BadField, MissingPaginationException as NoPage};
BadField::forArrayElement('real');
new NoPage('real');
PHP;

        self::assertSame([
            'src/Example.php:4' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::forArrayElement', 'endLine' => 4],
            'src/Example.php:5' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct', 'endLine' => 5],
        ], ExceptionCallSiteScanner::scan($source, 'src/Example.php'));
    }

    public function test_四型のconstructorと任意のstatic_factoryを検出する(): void
    {
        $source = <<<'PHP'
<?php
namespace Example;
use Shimoning\ColorMeShopApi\Exceptions\{
    InvalidFieldException as BadField,
    MissingFieldException as NoField,
    InvalidPaginationException as BadPage,
    MissingPaginationException as NoPage
};
new BadField('a');
new NoField('b');
new BadPage('c');
new NoPage('d');
BadField::fromValue('a');
NoField::fromValue('b');
BadPage::fromValue('c');
NoPage::fromValue('d');
PHP;

        self::assertSame([
            'src/Example.php:9' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::__construct', 'endLine' => 9],
            'src/Example.php:10' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::__construct', 'endLine' => 10],
            'src/Example.php:11' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException::__construct', 'endLine' => 11],
            'src/Example.php:12' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct', 'endLine' => 12],
            'src/Example.php:13' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::fromValue', 'endLine' => 13],
            'src/Example.php:14' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::fromValue', 'endLine' => 14],
            'src/Example.php:15' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException::fromValue', 'endLine' => 15],
            'src/Example.php:16' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::fromValue', 'endLine' => 16],
        ], ExceptionCallSiteScanner::scan($source, 'src/Example.php'));
    }

    public function test_複数行の生成式は閉じ括弧までを範囲とする(): void
    {
        $source = <<<'PHP'
<?php
namespace Shimoning\ColorMeShopApi\Exceptions;
InvalidFieldException::for(
    nested('value', call()),
);
new MissingFieldException(
    nested('value', call()),
);
new InvalidPaginationException;
PHP;

        self::assertSame([
            'src/Example.php:3' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::for', 'endLine' => 5],
            'src/Example.php:6' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::__construct', 'endLine' => 8],
            'src/Example.php:9' => ['route' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException::__construct', 'endLine' => 9],
        ], ExceptionCallSiteScanner::scan($source, 'src/Example.php'));
    }

    public function test_開始行と終了行と範囲内のみ一致する(): void
    {
        self::assertTrue(ExceptionCallSiteScanner::containsLine(3, 5, 3));
        self::assertTrue(ExceptionCallSiteScanner::containsLine(3, 5, 4));
        self::assertTrue(ExceptionCallSiteScanner::containsLine(3, 5, 5));
        self::assertFalse(ExceptionCallSiteScanner::containsLine(3, 5, 2));
        self::assertFalse(ExceptionCallSiteScanner::containsLine(3, 5, 6));
    }

    public function test_同一行に複数の生成箇所があれば黙って上書きしない(): void
    {
        $source = <<<'PHP'
<?php
namespace Shimoning\ColorMeShopApi\Exceptions;
MissingFieldException::for('first'); MissingFieldException::for('second');
PHP;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('src/Example.php:3');
        ExceptionCallSiteScanner::scan($source, 'src/Example.php');
    }
}
