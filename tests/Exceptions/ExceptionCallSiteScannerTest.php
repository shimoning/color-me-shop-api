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
            'src/Example.php:12' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::for',
            'src/Example.php:13' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException::__construct',
            'src/Example.php:14' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct',
            'src/Example.php:15' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct',
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
            'src/Example.php:3' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::for',
            'src/Example.php:5' => 'Shimoning\ColorMeShopApi\Exceptions\MissingFieldException::for',
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
            'src/Example.php:4' => 'Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException::forArrayElement',
            'src/Example.php:5' => 'Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::__construct',
        ], ExceptionCallSiteScanner::scan($source, 'src/Example.php'));
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
