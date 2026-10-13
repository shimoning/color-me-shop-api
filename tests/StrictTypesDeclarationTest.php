<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class StrictTypesDeclarationTest extends TestCase
{
    public function test_対象となるすべてのPHPファイルでstrict_typesが有効になっている(): void
    {
        $filesWithoutStrictTypes = [];

        foreach (self::targetPhpFiles() as $file) {
            $contents = \file_get_contents($file);
            self::assertIsString($contents, $file);

            if (! \preg_match('/\A(?:#![^\r\n]+\r?\n)?<\?php\r?\n\r?\ndeclare\(strict_types=1\);\r?\n/', $contents)) {
                $filesWithoutStrictTypes[] = self::relativePath($file);
            }
        }

        self::assertSame(
            [],
            $filesWithoutStrictTypes,
            "strict_types 宣言がないか、配置が不正な PHP ファイルがあります:\n"
                . \implode("\n", $filesWithoutStrictTypes),
        );
    }

    /** @return list<string> */
    private static function targetPhpFiles(): array
    {
        $root = \dirname(__DIR__);
        $files = [];

        foreach (['src', 'tests', 'examples', 'bootstrap'] as $directory) {
            $path = $root . \DIRECTORY_SEPARATOR . $directory;
            if (! \is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        $client = $root . \DIRECTORY_SEPARATOR . 'client';
        if (\is_file($client)) {
            $contents = \file_get_contents($client);
            self::assertIsString($contents, $client);
            if (\preg_match('/\A(?:#![^\r\n]+\r?\n)?<\?php/', $contents)) {
                $files[] = $client;
            }
        }

        \sort($files);
        return $files;
    }

    private static function relativePath(string $file): string
    {
        return \substr($file, \strlen(\dirname(__DIR__)) + 1);
    }
}
