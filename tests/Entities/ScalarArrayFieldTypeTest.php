<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryMethod;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Sale\Delivery as SalesDelivery;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\Segment;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters as SalesSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class ScalarArrayFieldTypeTest extends TestCase
{
    /** @var array<string, string> 宣言へ移せないプロパティと理由 */
    private const EXCLUDED_PROPERTIES = [];

    #[DataProvider('newScalarArrayProvider')]
    public function test_未検証だったscalar配列の不正要素を拒否する(
        string $class,
        string $apiField,
        string $scalar,
    ): void {
        $this->assertInvalidElement($class, $apiField, $scalar);
    }

    /** @return array<string, array{class-string<Entity>, string, string}> */
    public static function newScalarArrayProvider(): array
    {
        return [
            '配送方法の利用不可決済ID' => [DeliveryMethod::class, 'unavailable_payment_ids', 'int'],
            'お届け先の明細ID' => [SalesDelivery::class, 'detail_ids', 'int'],
            '分割受注の兄弟ID' => [Segment::class, 'siblings_sale_ids', 'int'],
            '受注検索の受注ID' => [SalesSearchParameters::class, 'ids', 'int'],
            '受注検索の顧客ID' => [SalesSearchParameters::class, 'customer_ids', 'int'],
            '受注検索の決済ID' => [SalesSearchParameters::class, 'payment_ids', 'int'],
            '受注検索の取得フィールド' => [SalesSearchParameters::class, 'fields', 'string'],
            '顧客検索の顧客ID' => [CustomerSearchParameters::class, 'ids', 'int'],
            '顧客検索の取得フィールド' => [CustomerSearchParameters::class, 'fields', 'string'],
        ];
    }

    public function test_お届け先更新入力も継承したscalar配列の不正要素を拒否する(): void
    {
        $this->assertInvalidElement(DeliveryUpdateInput::class, 'detail_ids', 'int');
    }

    public function test_scalar配列アノテーションには対応するFIELD_TYPES宣言がある(): void
    {
        $violations = [];
        $checked = 0;

        foreach (self::sourceEntityClasses() as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getProperties() as $property) {
                $scalar = self::scalarAnnotation($property);
                if ($scalar === null) {
                    continue;
                }

                $checked++;
                $relative = \substr($class, \strlen('Shimoning\\ColorMeShopApi\\Entities\\'));
                $id = $relative . '::$' . $property->getName();
                if (isset(self::EXCLUDED_PROPERTIES[$id])) {
                    continue;
                }

                $definition = $class::FIELD_TYPES[$property->getName()] ?? null;
                if ($definition !== ['array' => true, 'scalar' => $scalar]) {
                    $violations[] = $id;
                }
            }
        }

        foreach (self::EXCLUDED_PROPERTIES as $property => $reason) {
            $this->assertNotSame('', \trim($reason), $property . ' の対象外理由が空です');
        }
        $this->assertGreaterThan(15, $checked, 'scalar 配列プロパティを検出できませんでした。');
        $this->assertSame([], $violations, 'FIELD_TYPES の scalar 配列宣言がありません: ' . \implode(', ', $violations));
    }

    #[DataProvider('declaredScalarArrayProvider')]
    public function test_宣言済みscalar配列はすべて不正要素を拒否する(
        string $class,
        string $apiField,
        string $scalar,
    ): void {
        $this->assertInvalidElement($class, $apiField, $scalar);
    }

    /** @return array<string, array{class-string<Entity>, string, string}> */
    public static function declaredScalarArrayProvider(): array
    {
        $cases = [];
        foreach (self::sourceEntityClasses() as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            foreach ($class::FIELD_TYPES as $property => $definition) {
                if (! \is_array($definition)
                    || ($definition['array'] ?? false) !== true
                    || ! \in_array($definition['scalar'] ?? null, ['int', 'string'], true)
                ) {
                    continue;
                }

                $apiField = $class::apiFieldName($property);
                $relative = \substr($class, \strlen('Shimoning\\ColorMeShopApi\\Entities\\'));
                $cases[$relative . '::$' . $property] = [$class, $apiField, $definition['scalar']];
            }
        }

        return $cases;
    }

    /** @param class-string<Entity> $class */
    private function assertInvalidElement(string $class, string $apiField, string $scalar): void
    {
        $invalid = $scalar === 'int' ? '1' : 1;

        try {
            new $class([$apiField => [$invalid]]);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                $class . " の API フィールド『{$apiField}』が不正です。"
                . "配列要素を {$scalar} に変換できませんでした。原因: 配列要素の型が不正です。",
                $exception->getMessage(),
            );
            $previous = $exception->getPrevious();
            $this->assertInstanceOf(\TypeError::class, $previous);
            $this->assertSame("配列要素が {$scalar} ではありません。", $previous->getMessage());
            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    private static function scalarAnnotation(ReflectionProperty $property): ?string
    {
        $document = $property->getDocComment();
        if (! \is_string($document)
            || \preg_match('/@var\s+list<(int|string)>(?:\|null)?(?:\s|\*\/)/', $document, $matches) !== 1
        ) {
            return null;
        }

        return $matches[1];
    }

    /** @return list<class-string<Entity>> */
    private static function sourceEntityClasses(): array
    {
        $classes = [];
        $base = \realpath(__DIR__ . '/../../src/Entities');
        self::assertIsString($base);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = \substr($file->getPathname(), \strlen($base) + 1, -4);
            $class = 'Shimoning\\ColorMeShopApi\\Entities\\'
                . \str_replace(\DIRECTORY_SEPARATOR, '\\', $relative);
            if (\class_exists($class) && \is_subclass_of($class, Entity::class)) {
                $classes[] = $class;
            }
        }

        \sort($classes);
        return $classes;
    }
}
