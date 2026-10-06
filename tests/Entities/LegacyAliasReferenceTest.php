<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * src/Entities の型宣言が互換用の旧クラス名に依存していないことを検証する。
 */
class LegacyAliasReferenceTest extends TestCase
{
    public function test_srcで旧クラス名を参照しない(): void
    {
        $violations = [];
        $base = \realpath(__DIR__ . '/../../src');
        self::assertIsString($base);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php' || $file->getPathname() === $base . '/Aliases.php') {
                continue;
            }
            $source = \file_get_contents($file->getPathname());
            self::assertIsString($source);
            $relativePath = 'src/' . \substr($file->getPathname(), \strlen($base) + 1);
            $violations = [...$violations, ...self::legacyReferences($source, $relativePath)];
        }

        $this->assertSame([], $violations, "src に旧クラス名への参照があります:\n" . \implode("\n", $violations));
    }

    public function test_トークン走査が旧クラス名への多様な参照を検出する(): void
    {
        $source = <<<'PHP'
<?php
namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Product\{Variant as LegacyVariant, Option};

/** @see \Shimoning\ColorMeShopApi\Entities\Sales\Sale */
final class Fixture
{
    /** @param list<Membership|null> $membership */
    public function setMembership(Membership $membership): void
    {
        $class = LegacyVariant::class;
        $option = new Option();
        if ($membership instanceof Membership) {
            return;
        }
    }

    /** @return array<string, \Shimoning\ColorMeShopApi\Entities\Gift\GiftCard> */
    public function cards(): array
    {
        return [];
    }
}
PHP;

        $violations = self::legacyReferences($source, 'fixture.php');

        foreach ([
            'fixture.php:4: Shimoning\\ColorMeShopApi\\Entities\\Product\\Variant',
            'fixture.php:4: Shimoning\\ColorMeShopApi\\Entities\\Product\\Option',
            'fixture.php:6: Shimoning\\ColorMeShopApi\\Entities\\Sales\\Sale',
            'fixture.php:9: Shimoning\\ColorMeShopApi\\Entities\\Customer\\Membership',
            'fixture.php:10: Shimoning\\ColorMeShopApi\\Entities\\Customer\\Membership',
            'fixture.php:12: Shimoning\\ColorMeShopApi\\Entities\\Product\\Variant',
            'fixture.php:13: Shimoning\\ColorMeShopApi\\Entities\\Product\\Option',
            'fixture.php:19: Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftCard',
        ] as $violation) {
            $this->assertContains($violation, $violations);
        }
        $this->assertSame(
            [],
            \array_values(\array_filter(
                $violations,
                static fn (string $violation): bool => \preg_match('/^fixture\.php:\d+: /', $violation) !== 1,
            )),
        );
    }

    public function test_クラス参照ではない名前と現行クラス名を誤検出しない(): void
    {
        $source = <<<'PHP'
<?php
namespace Shimoning\ColorMeShopApi\Entities\Product;

use function Shimoning\ColorMeShopApi\Entities\Product\Variant;
use const Shimoning\ColorMeShopApi\Entities\Product\Option;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant as CurrentVariant;

function Variant(): void {}

final class Fixture
{
    /** @see docs/entities/Variant.md */
    public const Variant = 'constant';

    public function Variant(): void
    {
        Variant();
        $this->Variant();
        $variant = new CurrentVariant();
        $sameShortName = new Variant\Variant();
    }
}
PHP;

        $this->assertSame([], self::legacyReferences($source, 'non-violation.php'));
    }

    public function test_指定されたPHPDocタグの型を検出する(): void
    {
        $source = <<<'PHP'
<?php
namespace Fixture;

/**
 * @var list<\Shimoning\ColorMeShopApi\Entities\Gift\GiftNoshi>
 * @property \Shimoning\ColorMeShopApi\Entities\Gift\GiftType $type
 * @method \Shimoning\ColorMeShopApi\Entities\Gift\GiftWrapping wrap(\Shimoning\ColorMeShopApi\Entities\Delivery\DeliveryDate $date)
 * @throws \Shimoning\ColorMeShopApi\Entities\Customer\Points
 * @see \Shimoning\ColorMeShopApi\Entities\Customer\CustomerPointsInput
 */
final class Fixture {}
PHP;

        $violations = self::legacyReferences($source, 'phpdoc.php');

        foreach ([
            'phpdoc.php:5: Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftNoshi',
            'phpdoc.php:6: Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftType',
            'phpdoc.php:7: Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftWrapping',
            'phpdoc.php:7: Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDate',
            'phpdoc.php:8: Shimoning\\ColorMeShopApi\\Entities\\Customer\\Points',
            'phpdoc.php:9: Shimoning\\ColorMeShopApi\\Entities\\Customer\\CustomerPointsInput',
        ] as $violation) {
            $this->assertContains($violation, $violations);
        }
    }

    public function test_プロパティ型に旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            foreach ($class->getProperties() as $property) {
                foreach (self::typeNames($property->getType()) as $type) {
                    if (isset(Aliases::MAP[$type])) {
                        $violations[] = $class->getName() . '::$' . $property->getName() . ': ' . $type;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "プロパティ型に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    public function test_FIELD_TYPESに旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            /** @var array<string, array<string, mixed>> $fieldTypes */
            $fieldTypes = $class->getConstant('FIELD_TYPES');
            foreach ($fieldTypes as $field => $definition) {
                foreach (['entity', 'value', 'enum'] as $kind) {
                    $type = $definition[$kind] ?? null;
                    if (\is_string($type) && isset(Aliases::MAP[$type])) {
                        $violations[] = $class->getName() . '::FIELD_TYPES[' . $field . '][' . $kind . ']: ' . $type;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "FIELD_TYPES に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    public function test_publicメソッドの戻り値型に旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach (self::typeNames($method->getReturnType()) as $type) {
                    if (isset(Aliases::MAP[$type])) {
                        $violations[] = $class->getName() . '::' . $method->getName() . '(): ' . $type;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "戻り値型に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    public function test_PHPDocのvarとreturnに旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            foreach ($class->getProperties() as $property) {
                self::collectDocViolations(
                    $violations,
                    $class,
                    $property->getDocComment(),
                    'var',
                    $class->getName() . '::$' . $property->getName(),
                );
            }
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                self::collectDocViolations(
                    $violations,
                    $class,
                    $method->getDocComment(),
                    'return',
                    $class->getName() . '::' . $method->getName() . '()',
                );
            }
        }

        $this->assertSame([], $violations, "PHPDoc に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    /** @return list<ReflectionClass<Entity>> */
    private static function entityReflections(): array
    {
        $reflections = [];
        $base = \realpath(__DIR__ . '/../../src/Entities');
        self::assertIsString($base);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = \substr($file->getPathname(), \strlen($base) + 1);
            $class = 'Shimoning\\ColorMeShopApi\\Entities\\'
                . \str_replace([\DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
            if (! \class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->getName() !== Entity::class && $reflection->isSubclassOf(Entity::class)) {
                $reflections[] = $reflection;
            }
        }

        return $reflections;
    }

    /** @return list<string> */
    private static function typeNames(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return \array_merge(...\array_map(self::typeNames(...), $type->getTypes()));
        }
        return [];
    }

    /** @param list<string> $violations */
    private static function collectDocViolations(
        array &$violations,
        ReflectionClass $class,
        string|false $doc,
        string $tag,
        string $location,
    ): void {
        if (! \is_string($doc)
            || \preg_match('/@' . $tag . '\\s+([^\\s*]+)/', $doc, $matches) !== 1
        ) {
            return;
        }

        foreach (self::docClassNames($class, $matches[1]) as $type) {
            if (isset(Aliases::MAP[$type])) {
                $violations[] = $location . ' @' . $tag . ': ' . $type;
            }
        }
    }

    /** @return list<string> */
    private static function docClassNames(ReflectionClass $class, string $type): array
    {
        \preg_match_all('/\\??\\b[A-Z][A-Za-z0-9_\\\\]*/', $type, $matches);
        $imports = self::imports($class);
        $classes = [];
        foreach ($matches[0] as $name) {
            $absolute = \str_starts_with($name, '\\');
            $name = \ltrim($name, '\\');
            $first = \strstr($name, '\\', true) ?: $name;
            if ($absolute) {
                $classes[] = $name;
            } elseif (isset($imports[$first])) {
                $classes[] = $imports[$first] . \substr($name, \strlen($first));
            } else {
                $classes[] = $class->getNamespaceName() . '\\' . $name;
            }
        }
        return $classes;
    }

    /** @return array<string, string> */
    private static function imports(ReflectionClass $class): array
    {
        $file = $class->getFileName();
        if (! \is_string($file)) {
            return [];
        }
        $source = \file_get_contents($file);
        if (! \is_string($source)) {
            return [];
        }
        \preg_match_all('/^use\\s+([^;]+);/m', $source, $matches);
        $imports = [];
        foreach ($matches[1] as $import) {
            $parts = \preg_split('/\\s+as\\s+/i', $import);
            if (! \is_array($parts)) {
                continue;
            }
            $name = $parts[0];
            $alias = $parts[1] ?? \substr($name, (int) \strrpos($name, '\\') + 1);
            $imports[$alias] = $name;
        }
        return $imports;
    }

    /** @return list<string> */
    private static function legacyReferences(string $source, string $file): array
    {
        $tokens = \PhpToken::tokenize($source, \TOKEN_PARSE);
        $legacyNames = [];
        foreach (\array_keys(Aliases::MAP) as $legacy) {
            $legacyNames[\strtolower($legacy)] = $legacy;
        }

        $violations = [];
        $namespace = '';
        /** @var array<string, string> $classImports */
        $classImports = [];
        $braceDepth = 0;
        $namespaceDepth = 0;

        for ($index = 0, $count = \count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];

            if ($token->id === \T_NAMESPACE) {
                [$namespace, $end, $bracketed] = self::parseNamespace($tokens, $index);
                $classImports = [];
                if ($bracketed) {
                    $namespaceDepth = $braceDepth + 1;
                }
                $index = $end;
                if ($bracketed) {
                    $braceDepth++;
                }
                continue;
            }

            if ($token->id === \T_USE) {
                $next = self::nextSignificant($tokens, $index);
                if ($next !== null && $tokens[$next]->text !== '(') {
                    [$end, $uses] = self::parseUse($tokens, $index, $braceDepth === $namespaceDepth);
                    foreach ($uses as [$name, $alias, $line, $isImport]) {
                        if ($isImport) {
                            $classImports[\strtolower($alias)] = $name;
                        }
                        $resolved = $isImport ? $name : self::resolveClassName($name, $namespace, $classImports);
                        self::addViolation($violations, $legacyNames, $file, $line, $resolved);
                    }
                    $index = $end;
                    continue;
                }
            }

            if ($token->id === \T_DOC_COMMENT) {
                foreach (self::docNames($token->text, $token->line) as [$name, $line]) {
                    $resolved = self::resolveClassName($name, $namespace, $classImports);
                    self::addViolation($violations, $legacyNames, $file, $line, $resolved);
                }
                continue;
            }

            if (self::isNameToken($token)
                && self::isClassReference($tokens, $index)
            ) {
                $resolved = self::resolveClassName($token->text, $namespace, $classImports);
                self::addViolation($violations, $legacyNames, $file, $token->line, $resolved);
            }

            if ($token->text === '{') {
                $braceDepth++;
            } elseif ($token->text === '}') {
                $braceDepth--;
            }
        }

        return \array_values(\array_unique($violations));
    }

    /** @param list<\PhpToken> $tokens
     * @return array{string, int, bool}
     */
    private static function parseNamespace(array $tokens, int $start): array
    {
        $name = '';
        for ($index = $start + 1, $count = \count($tokens); $index < $count; $index++) {
            if ($tokens[$index]->text === ';' || $tokens[$index]->text === '{') {
                return [\trim($name, '\\'), $index, $tokens[$index]->text === '{'];
            }
            if (! self::isIgnorable($tokens[$index])) {
                $name .= $tokens[$index]->text;
            }
        }
        return ['', $start, false];
    }

    /**
     * @param list<\PhpToken> $tokens
     * @return array{int, list<array{string, string, int, bool}>}
     */
    private static function parseUse(array $tokens, int $start, bool $isNamespaceImport): array
    {
        $text = '';
        $line = $tokens[$start]->line;
        $end = $start;
        for ($index = $start + 1, $count = \count($tokens); $index < $count; $index++) {
            $end = $index;
            if ($tokens[$index]->text === ';') {
                break;
            }
            $text .= self::isIgnorable($tokens[$index]) ? ' ' : $tokens[$index]->text;
        }
        $text = \trim($text);

        if (\preg_match('/^(function|const)\\b/i', $text) === 1) {
            return [$end, []];
        }

        $uses = [];
        if (\str_contains($text, '{')) {
            [$prefix, $members] = \explode('{', \rtrim($text, '}'), 2);
            foreach (\explode(',', $members) as $member) {
                $member = \trim($member);
                if (\preg_match('/^(function|const)\\b/i', $member) === 1) {
                    continue;
                }
                [$name, $alias] = self::useNameAndAlias(\trim($prefix, " \t\n\r\0\x0B\\\\") . '\\' . $member);
                $uses[] = [$name, $alias, $line, $isNamespaceImport];
            }
        } else {
            foreach (\explode(',', $text) as $member) {
                [$name, $alias] = self::useNameAndAlias($member);
                $uses[] = [$name, $alias, $line, $isNamespaceImport];
            }
        }

        return [$end, $uses];
    }

    /** @return array{string, string} */
    private static function useNameAndAlias(string $use): array
    {
        $parts = \preg_split('/\\bas\\b/i', \trim($use));
        self::assertIsArray($parts);
        $name = \trim($parts[0], " \t\n\r\0\x0B\\\\");
        $position = \strrpos($name, '\\');
        $alias = isset($parts[1])
            ? \trim($parts[1])
            : ($position === false ? $name : \substr($name, $position + 1));
        return [$name, $alias];
    }

    /** @param list<\PhpToken> $tokens */
    private static function isClassReference(array $tokens, int $index): bool
    {
        $previous = self::previousSignificant($tokens, $index);
        $next = self::nextSignificant($tokens, $index);

        if ($next !== null && $tokens[$next]->id === \T_DOUBLE_COLON) {
            return true;
        }
        if ($previous !== null && \in_array($tokens[$previous]->id, [\T_NEW, \T_INSTANCEOF, \T_EXTENDS], true)) {
            return true;
        }
        if (self::isInClassList($tokens, $index, \T_IMPLEMENTS)
            || self::isInClassList($tokens, $index, \T_EXTENDS)
            || self::isInCatchType($tokens, $index)
            || self::isTypeBeforeVariable($tokens, $index)
            || self::isReturnType($tokens, $index)
            || self::isAttributeName($tokens, $index)
        ) {
            return true;
        }
        return false;
    }

    /** @param list<\PhpToken> $tokens */
    private static function isInClassList(array $tokens, int $index, int $keyword): bool
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];
            if (self::isIgnorable($token) || $token->text === ',' || $token->text === '&') {
                continue;
            }
            if ($token->id === $keyword) {
                return true;
            }
            if (\in_array($token->text, [';', '{', '}', '(', ')'], true)) {
                return false;
            }
            if (self::isNameToken($token)) {
                continue;
            }
            return false;
        }
        return false;
    }

    /** @param list<\PhpToken> $tokens */
    private static function isInCatchType(array $tokens, int $index): bool
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];
            if (self::isIgnorable($token) || $token->text === '|' || self::isNameToken($token)) {
                continue;
            }
            if ($token->text === '(') {
                $before = self::previousSignificant($tokens, $cursor);
                return $before !== null && $tokens[$before]->id === \T_CATCH;
            }
            return false;
        }
        return false;
    }

    /** @param list<\PhpToken> $tokens */
    private static function isTypeBeforeVariable(array $tokens, int $index): bool
    {
        for ($cursor = $index + 1, $count = \count($tokens); $cursor < $count; $cursor++) {
            $token = $tokens[$cursor];
            if (self::isIgnorable($token) || self::isNameToken($token)
                || \in_array($token->text, ['?', '|', '&', '(', ')'], true)
            ) {
                continue;
            }
            return $token->id === \T_VARIABLE;
        }
        return false;
    }

    /** @param list<\PhpToken> $tokens */
    private static function isReturnType(array $tokens, int $index): bool
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];
            if (self::isIgnorable($token) || self::isNameToken($token)
                || \in_array($token->text, ['?', '|', '&', '(', ')'], true)
            ) {
                continue;
            }
            if ($token->text !== ':') {
                return false;
            }
            $before = self::previousSignificant($tokens, $cursor);
            return $before !== null && $tokens[$before]->text === ')';
        }
        return false;
    }

    /** @param list<\PhpToken> $tokens */
    private static function isAttributeName(array $tokens, int $index): bool
    {
        $parentheses = 0;
        $afterSeparator = false;
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];
            if ($token->text === ')') {
                $parentheses++;
            } elseif ($token->text === '(') {
                if ($parentheses === 0) {
                    return false;
                }
                $parentheses--;
            } elseif ($token->text === ']' && $parentheses === 0) {
                return false;
            } elseif ($token->id === \T_ATTRIBUTE && $parentheses === 0) {
                return true;
            } elseif ($token->text === ',' && $parentheses === 0) {
                $afterSeparator = true;
            } elseif ($afterSeparator && $parentheses === 0 && $token->text === '[') {
                return false;
            }
        }
        return false;
    }

    /**
     * @return list<array{string, int}>
     */
    private static function docNames(string $doc, int $startLine): array
    {
        $names = [];
        \preg_match_all(
            '/@(var|param|return|property|method|throws|see)\\s+([^\\r\\n*]+)/i',
            $doc,
            $annotations,
            \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER,
        );
        foreach ($annotations as $annotation) {
            $tag = \strtolower($annotation[1][0]);
            $content = \trim($annotation[2][0]);
            if ($tag === 'see' && self::isDocPath($content)) {
                continue;
            }
            $type = self::docTypePart($tag, $content);
            \preg_match_all(
                '/(?<![A-Za-z0-9_\\\\])\\\\?[A-Z][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*/',
                $type,
                $matches,
                \PREG_OFFSET_CAPTURE,
            );
            foreach ($matches[0] as [$name, $offset]) {
                if ($tag === 'method' && \preg_match('/^\\s*\\(/', \substr($type, $offset + \strlen($name))) === 1) {
                    continue;
                }
                $line = $startLine + \substr_count($doc, "\n", 0, $annotation[0][1]);
                $names[] = [$name, $line];
            }
        }
        return $names;
    }

    private static function isDocPath(string $content): bool
    {
        $first = \strtok($content, " \t") ?: '';
        return \preg_match('~^(?:https?://|[./]|docs/)~i', $first) === 1
            || \preg_match('/\\.(?:md|html?)(?:#|$)/i', $first) === 1;
    }

    private static function docTypePart(string $tag, string $content): string
    {
        if ($tag === 'method') {
            return $content;
        }
        if ($tag === 'see') {
            return \strtok($content, " \t") ?: '';
        }
        $variable = \strpos($content, '$');
        if ($variable !== false && \in_array($tag, ['var', 'param', 'property'], true)) {
            return \substr($content, 0, $variable);
        }

        $depth = 0;
        $length = \strlen($content);
        for ($index = 0; $index < $length; $index++) {
            if ($content[$index] === '<' || $content[$index] === '(') {
                $depth++;
            } elseif ($content[$index] === '>' || $content[$index] === ')') {
                $depth--;
            } elseif (\ctype_space($content[$index]) && $depth === 0) {
                $before = \rtrim(\substr($content, 0, $index));
                $after = \ltrim(\substr($content, $index));
                if (! \str_ends_with($before, '|') && ! \str_ends_with($before, '&')
                    && ! \str_starts_with($after, '|') && ! \str_starts_with($after, '&')
                ) {
                    return $before;
                }
            }
        }
        return $content;
    }

    /** @param array<string, string> $imports */
    private static function resolveClassName(string $name, string $namespace, array $imports): string
    {
        if (\str_starts_with($name, '\\')) {
            return \ltrim($name, '\\');
        }
        if (\str_starts_with(\strtolower($name), 'namespace\\')) {
            return \trim($namespace . '\\' . \substr($name, 10), '\\');
        }

        $separator = \strpos($name, '\\');
        $first = $separator === false ? $name : \substr($name, 0, $separator);
        $import = $imports[\strtolower($first)] ?? null;
        if ($import !== null) {
            return $import . ($separator === false ? '' : \substr($name, $separator));
        }
        return \trim($namespace . '\\' . $name, '\\');
    }

    /**
     * @param list<string> $violations
     * @param array<string, string> $legacyNames
     */
    private static function addViolation(
        array &$violations,
        array $legacyNames,
        string $file,
        int $line,
        string $resolved,
    ): void {
        $legacy = $legacyNames[\strtolower($resolved)] ?? null;
        if ($legacy !== null) {
            $violations[] = $file . ':' . $line . ': ' . $legacy;
        }
    }

    private static function isNameToken(\PhpToken $token): bool
    {
        return \in_array($token->id, [\T_STRING, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED, \T_NAME_RELATIVE], true);
    }

    private static function isIgnorable(\PhpToken $token): bool
    {
        return \in_array($token->id, [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true);
    }

    /** @param list<\PhpToken> $tokens */
    private static function previousSignificant(array $tokens, int $index): ?int
    {
        for ($index--; $index >= 0; $index--) {
            if (! self::isIgnorable($tokens[$index])) {
                return $index;
            }
        }
        return null;
    }

    /** @param list<\PhpToken> $tokens */
    private static function nextSignificant(array $tokens, int $index): ?int
    {
        for ($index++, $count = \count($tokens); $index < $count; $index++) {
            if (! self::isIgnorable($tokens[$index])) {
                return $index;
            }
        }
        return null;
    }
}
