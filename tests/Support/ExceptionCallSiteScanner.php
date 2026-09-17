<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Support;

/**
 * src/ の明示的な例外 factory 呼び出しと constructor 呼び出しを列挙する。
 */
final class ExceptionCallSiteScanner
{
    private const EXCEPTIONS = 'Shimoning\\ColorMeShopApi\\Exceptions\\';
    private const TARGET_CLASSES = [
        self::EXCEPTIONS . 'InvalidFieldException',
        self::EXCEPTIONS . 'MissingFieldException',
        self::EXCEPTIONS . 'InvalidPaginationException',
        self::EXCEPTIONS . 'MissingPaginationException',
    ];

    /**
     * @return array<string, array{route: string, endLine: int}> 相対パス:開始行 => 経路と閉じ括弧の行
     */
    public static function scan(string $source, string $path): array
    {
        $tokens = self::codeTokens($source);
        $namespace = '';
        $imports = [];
        $depth = 0;
        $namespaceDepth = 0;
        $sites = [];

        foreach ($tokens as $index => $token) {
            if ($token['text'] === '{') {
                ++$depth;
                continue;
            }
            if ($token['text'] === '}') {
                --$depth;
                continue;
            }

            if ($token['id'] === \T_NAMESPACE) {
                $name = self::nameAt($tokens, $index + 1);
                $namespace = $name === null ? '' : \trim($name[0], '\\');
                $imports = [];
                $afterName = $name === null ? $index + 1 : $name[1] + 1;
                $namespaceDepth = ($tokens[$afterName]['text'] ?? null) === '{' ? $depth + 1 : $depth;
                continue;
            }

            if ($token['id'] === \T_USE && $depth === $namespaceDepth) {
                self::readImports($tokens, $index + 1, $imports);
                continue;
            }

            if ($token['id'] === \T_NEW) {
                $name = self::nameAt($tokens, $index + 1);
                if ($name === null) {
                    continue;
                }
                $class = self::resolve($name[0], $namespace, $imports);
                if (\in_array($class, self::TARGET_CLASSES, true)) {
                    $opening = $name[1] + 1;
                    $endLine = ($tokens[$opening]['text'] ?? null) === '('
                        ? self::closingParenthesisLine($tokens, $opening)
                        : $token['line'];
                    self::record($sites, $path . ':' . $token['line'], $class . '::__construct', $endLine);
                }
                continue;
            }

            $name = self::nameAt($tokens, $index);
            if ($name === null || ($tokens[$name[1] + 1]['id'] ?? null) !== \T_DOUBLE_COLON) {
                continue;
            }
            $method = $tokens[$name[1] + 2] ?? null;
            if (
                $method === null
                || \preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $method['text']) !== 1
                || ($tokens[$name[1] + 3]['text'] ?? null) !== '('
            ) {
                continue;
            }
            $class = self::resolve($name[0], $namespace, $imports);
            if (\in_array($class, self::TARGET_CLASSES, true)) {
                self::record(
                    $sites,
                    $path . ':' . $token['line'],
                    $class . '::' . $method['text'],
                    self::closingParenthesisLine($tokens, $name[1] + 3),
                );
            }
        }

        return $sites;
    }

    public static function containsLine(int $startLine, int $endLine, int $line): bool
    {
        return $startLine <= $line && $line <= $endLine;
    }

    /**
     * @param list<array{id: int|null, text: string, line: int}> $tokens
     */
    private static function closingParenthesisLine(array $tokens, int $opening): int
    {
        $depth = 0;
        for ($i = $opening; isset($tokens[$i]); ++$i) {
            if ($tokens[$i]['text'] === '(') {
                ++$depth;
            } elseif ($tokens[$i]['text'] === ')' && --$depth === 0) {
                return $tokens[$i]['line'];
            }
        }

        throw new \RuntimeException('呼び出し式の閉じ括弧が見つかりません: ' . $tokens[$opening]['line']);
    }

    /** @param array<string, array{route: string, endLine: int}> $sites */
    private static function record(array &$sites, string $location, string $route, int $endLine): void
    {
        if (isset($sites[$location])) {
            throw new \RuntimeException('同一行に複数の生成箇所があります: ' . $location);
        }
        $sites[$location] = ['route' => $route, 'endLine' => $endLine];
    }

    /**
     * 変数経由の $class::for() / new $exceptionClass は、実行時の型が分からないため対象外。
     * src/ に導入する場合は、この解析制約と対応する provider ケースを再検討する。
     *
     * @return list<array{id: int|null, text: string, line: int}>
     */
    private static function codeTokens(string $source): array
    {
        $result = [];
        $line = 1;
        $quoted = null;
        $heredoc = false;

        foreach (\token_get_all($source) as $part) {
            $id = \is_array($part) ? $part[0] : null;
            $text = \is_array($part) ? $part[1] : $part;
            $startLine = \is_array($part) ? $part[2] : $line;
            $line = $startLine + \substr_count($text, "\n");

            if ($heredoc) {
                if ($id === \T_END_HEREDOC) {
                    $heredoc = false;
                }
                continue;
            }
            if ($quoted !== null) {
                if ($text === $quoted) {
                    $quoted = null;
                }
                continue;
            }
            if ($id === \T_START_HEREDOC) {
                $heredoc = true;
                continue;
            }
            if ($text === '"' || $text === '`') {
                $quoted = $text;
                continue;
            }
            if (\in_array($id, [
                \T_COMMENT,
                \T_DOC_COMMENT,
                \T_WHITESPACE,
                \T_CONSTANT_ENCAPSED_STRING,
                \T_ENCAPSED_AND_WHITESPACE,
                \T_INLINE_HTML,
            ], true)) {
                continue;
            }
            $result[] = ['id' => $id, 'text' => $text, 'line' => $startLine];
        }

        return $result;
    }

    /**
     * @param list<array{id: int|null, text: string, line: int}> $tokens
     * @return array{string, int}|null
     */
    private static function nameAt(array $tokens, int $index): ?array
    {
        $name = '';
        $last = $index - 1;
        for ($i = $index; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];
            if (! \in_array($token['id'], [
                \T_STRING,
                \T_NAME_QUALIFIED,
                \T_NAME_FULLY_QUALIFIED,
                \T_NAME_RELATIVE,
                \T_NS_SEPARATOR,
            ], true)) {
                break;
            }
            $name .= $token['text'];
            $last = $i;
        }

        return $name === '' ? null : [$name, $last];
    }

    /**
     * @param list<array{id: int|null, text: string, line: int}> $tokens
     * @param array<string, string> $imports
     */
    private static function readImports(array $tokens, int $index, array &$imports): void
    {
        $name = '';
        $alias = null;
        $groupPrefix = '';
        for ($i = $index; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];
            if ($token['id'] === \T_FUNCTION || $token['id'] === \T_CONST) {
                if ($name === '' && $groupPrefix === '') {
                    return; // 関数・定数の import はクラス名解決に関係しない。
                }
                throw new \RuntimeException('Unsupported mixed use syntax at line ' . $token['line']);
            }
            if ($token['id'] === \T_AS) {
                $alias = $tokens[$i + 1]['text'] ?? null;
                ++$i;
                continue;
            }
            if ($token['text'] === '{') {
                $groupPrefix = \rtrim($name, '\\') . '\\';
                $name = '';
                continue;
            }
            if ($token['text'] === '}') {
                self::addImport($imports, $groupPrefix . $name, $alias);
                $name = '';
                $alias = null;
                $groupPrefix = '';
                continue;
            }
            if ($token['text'] === ',' || $token['text'] === ';') {
                self::addImport($imports, $groupPrefix . $name, $alias);
                $name = '';
                $alias = null;
                if ($token['text'] === ';') {
                    return;
                }
                continue;
            }
            $name .= $token['text'];
        }
    }

    /** @param array<string, string> $imports */
    private static function addImport(array &$imports, string $name, ?string $alias): void
    {
        if ($name === '') {
            return;
        }
        $class = \trim($name, '\\');
        $parts = \explode('\\', $class);
        $imports[\strtolower($alias ?? \end($parts))] = $class;
    }

    /** @param array<string, string> $imports */
    private static function resolve(string $name, string $namespace, array $imports): string
    {
        if (\str_starts_with($name, '\\')) {
            return \ltrim($name, '\\');
        }
        if (\str_starts_with($name, 'namespace\\')) {
            return $namespace . '\\' . \substr($name, 10);
        }

        $parts = \explode('\\', $name, 2);
        $import = $imports[\strtolower($parts[0])] ?? null;
        if ($import !== null) {
            return $import . (isset($parts[1]) ? '\\' . $parts[1] : '');
        }

        return $namespace === '' ? $name : $namespace . '\\' . $name;
    }
}
