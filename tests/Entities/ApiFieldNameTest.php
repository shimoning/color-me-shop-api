<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse;
use Shimoning\ColorMeShopApi\Tests\TestCase;

/**
 * API のフィールド名が往復すること (取り込み → 配列化) を検証する。
 *
 * Entity は取り込み時にアンダースコア区切りを camelCase へ、配列化時に大文字の前へ
 * アンダースコアを挿入する形で変換しているが、この2つは対称ではない。
 * 例えば shop_mail_1 は shopMail1 として取り込まれるが、配列化すると shop_mail1 になり
 * 元のキーに戻らない。
 *
 * 期待するフィールド名の一覧は公式の OpenAPI 仕様を基本とし、
 * 公式仕様にない実 API の観測フィールドも補完している。
 *
 *   https://api.shop-pro.jp/v1/spec/open_api.json (2026-09-17 取得)
 *   Error::field は docs/api-error-responses.md の実 API 観測結果による。
 *   OAuth\\AccessToken は RFC 6749 §5.1 と tests/Fixtures/oauth_token.json、
 *   OAuth\\ErrorResponse の error / error_description / error_uri は RFC 6749 §5.2、
 *   state は RFC 6749 §4.1.2.1 (認可コード) / §4.2.2.1 (インプリシット) と docs/adr/0007 による。
 *
 * 更新するときは同じ仕様から tests/Fixtures/api_field_names.json を作り直し、
 * 実 API でのみ観測したフィールドを追記すること。
 */
class ApiFieldNameTest extends TestCase
{
    /** @var array<string, string> 対象外クラスと理由 */
    private const EXCLUDED_ENTITIES = [
        'Entity' => '共通基底クラスで、API フィールドを宣言しない',
        'Collection' => 'Entity を継承せず、toArray() を持たないコレクション',
        'Page' => 'Entity を継承せず、toArray() を持たないページ付きコレクション',
        'OAuth\\Options' => 'Entity を継承しないアプリ設定 DTO',
        'Product\\Category' => '抽象基底クラスで、具象クラスを個別に検証する',
        'Delivery\\Weight' => 'weight / areas は API の重量別送料タプルに付けたライブラリ独自名',
        'Payment\\CodFee' => 'upper_limit / fee は API の代引き手数料タプルに付けたライブラリ独自名',
    ];

    /** @var array<string, array<string, string>> 公式フィールド外の内部フィールドと理由 */
    private const INTERNAL_FIELDS = [
        'Sales\\SaleUpdateInput' => [
            'id' => 'PUT /v1/sales/{sale_id} の path parameter 由来。現行実装では body にも含めて送信される (Issue #53)',
        ],
    ];

    public function test_空のフィールド一覧は登録として認めない(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Sales\\Sale');

        self::assertRegisteredFieldsNotEmpty([
            'Shop\\Shop' => ['id'],
            'Sales\\Sale' => [],
        ]);
    }

    /** @param array<string, list<string>> $registrations */
    private static function assertRegisteredFieldsNotEmpty(array $registrations): void
    {
        foreach ($registrations as $relative => $fields) {
            self::assertNotEmpty($fields, $relative . ' の API フィールド名が登録されていません');
        }
    }

    public function test_すべてのEntityが検証対象に含まれている(): void
    {
        $registrations = self::fixtureArray('api_field_names.json');
        self::assertRegisteredFieldsNotEmpty($registrations);

        $registered = \array_keys($registrations);
        $excluded = \array_keys(self::EXCLUDED_ENTITIES);
        $discovered = self::discoveredClasses();
        $entities = self::entityClasses($discovered);

        $missing = \array_values(\array_diff($entities, $registered, $excluded));
        $this->assertSame([], $missing, \sprintf(
            'api_field_names.json に未登録の Entity: %s。登録するか、EXCLUDED_ENTITIES に理由付きで追加してください。',
            \implode(', ', $missing),
        ));

        $stale = \array_values(\array_diff($excluded, $discovered));
        $this->assertSame([], $stale, '存在しないクラスが EXCLUDED_ENTITIES にあります: ' . \implode(', ', $stale));

        $invalidRegistered = \array_values(\array_diff($registered, $entities));
        $this->assertSame([], $invalidRegistered, '具象 Entity ではない登録: ' . \implode(', ', $invalidRegistered));

        $unclassified = \array_values(\array_diff($discovered, $entities, $excluded));
        $this->assertSame([], $unclassified, '基底・抽象・非 Entity クラスの対象外理由がありません: ' . \implode(', ', $unclassified));

        $overlap = \array_values(\array_intersect($registered, $excluded));
        $this->assertSame([], $overlap, '登録と対象外の両方にあるクラス: ' . \implode(', ', $overlap));

        foreach (self::EXCLUDED_ENTITIES as $class => $reason) {
            $this->assertNotSame('', \trim($reason), $class . ' の対象外理由が空です');
        }
    }

    /**
     * @param list<string> $discovered
     * @return list<string>
     */
    private static function entityClasses(array $discovered): array
    {
        return \array_values(\array_filter($discovered, static function (string $relative): bool {
            $reflection = new ReflectionClass('Shimoning\\ColorMeShopApi\\Entities\\' . $relative);
            return $reflection->isSubclassOf(Entity::class) && ! $reflection->isAbstract();
        }));
    }

    /** @return list<string> src/Entities 配下のクラス名 (Entities 名前空間からの相対名) */
    private static function discoveredClasses(): array
    {
        $root = __DIR__ . '/../../src/Entities';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $classes = [];

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = \substr($file->getPathname(), \strlen($root) + 1, -4);
            $class = 'Shimoning\\ColorMeShopApi\\Entities\\' . \str_replace('/', '\\', $relative);
            if (! \class_exists($class)) {
                throw new \RuntimeException('Entity クラスを読み込めません: ' . $class);
            }
            $classes[] = \str_replace('/', '\\', $relative);
        }

        \sort($classes);
        return $classes;
    }

    /**
     * @return array<string, array{class-string, array<string>}>
     */
    public static function entityFieldProvider(): array
    {
        $cases = [];
        foreach (self::fixtureArray('api_field_names.json') as $relative => $fields) {
            $cases[$relative] = ['Shimoning\\ColorMeShopApi\\Entities\\' . $relative, $fields];
        }
        return $cases;
    }

    public function test_検証対象のフィールドが読み込めている(): void
    {
        $fields = self::fixtureArray('api_field_names.json');

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(200, \array_sum(\array_map('count', $fields)));
    }

    public function test_内部フィールドは公式一覧の外にあり配列化のキーに存在する(): void
    {
        $registrations = self::fixtureArray('api_field_names.json');

        foreach (self::INTERNAL_FIELDS as $relative => $fields) {
            $this->assertArrayHasKey($relative, $registrations, $relative . ' が公式フィールド一覧にありません');
            $this->assertNotEmpty($fields, $relative . ' の内部フィールドが空です');

            $class = 'Shimoning\\ColorMeShopApi\\Entities\\' . $relative;
            $keys = \array_keys(self::newEntity($class, [])->toArray());

            foreach ($fields as $field => $reason) {
                $this->assertNotSame('', \trim($reason), $relative . '.' . $field . ' の理由が空です');
                $this->assertNotContains($field, $registrations[$relative], $relative . '.' . $field . ' が公式フィールド一覧と重複しています');
                $this->assertContains(
                    $field,
                    $keys,
                    $relative . '.' . $field . ' が toArray() のキーにありません。INTERNAL_FIELDS の宣言を削除してください。',
                );
            }
        }
    }

    /**
     * 配列化したときに、API と同じフィールド名で取り出せること。
     *
     * toArray() は宣言済みのプロパティをすべて列挙するため、
     * ここが通れば「API のフィールド名に対応するプロパティが存在し、
     * かつ同じ名前で取り出せる」ことが担保できる。
     */
    #[DataProvider('entityFieldProvider')]
    public function test_配列化するとAPIと同じフィールド名になる(string $class, array $fields): void
    {
        $keys = \array_keys(self::newEntity($class, [])->toArray());
        $shortName = (new ReflectionClass($class))->getShortName();

        foreach ($fields as $field) {
            $this->assertContains(
                $field,
                $keys,
                \sprintf('%s: toArray() のキーに %s がない', $shortName, $field),
            );
        }
    }

    /**
     * API のフィールド名で渡した値が、対応するプロパティに取り込まれること。
     *
     * 型に応じた値を用意できるスカラーのプロパティだけを対象にする。
     * enum や値オブジェクトは変換に固有の形式が必要なため除外する。
     */
    #[DataProvider('entityFieldProvider')]
    public function test_APIのフィールド名で値を取り込める(string $class, array $fields): void
    {
        $reflection = new ReflectionClass($class);
        $shortName = $reflection->getShortName();
        $checked = 0;

        foreach ($fields as $field) {
            $property = self::propertyFor($class, $reflection, $field);
            if ($property === null) {
                $this->fail(\sprintf('%s: %s に対応するプロパティが見つからない', $shortName, $field));
            }

            $value = self::sampleValue($reflection->getProperty($property));
            if ($value === null) {
                continue;
            }

            $checked++;
            $entity = self::newEntity($class, [$field => $value]);
            $this->assertSame(
                $value,
                $entity->toArray()[$field] ?? null,
                \sprintf('%s: %s で渡した値が取り出せない', $shortName, $field),
            );
        }

        // スカラーのプロパティを持たないクラスもあるため、件数は要求しない
        $this->addToAssertionCount($checked === 0 ? 1 : 0);
    }

    /**
     * API のフィールド名に対応するプロパティ名を引く
     *
     * @param class-string<Entity> $class
     */
    private static function propertyFor(string $class, ReflectionClass $reflection, string $field): ?string
    {
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || $property->isPrivate()) {
                continue;
            }
            if ($class::apiFieldName($property->getName()) === $field) {
                return $property->getName();
            }
        }
        return null;
    }

    /**
     * プロパティの型に応じた検証用の値。判断できない型は null を返す。
     */
    private static function sampleValue(\ReflectionProperty $property): int|string|bool|null
    {
        $type = $property->getType();
        if (! $type instanceof \ReflectionNamedType) {
            return null;
        }

        return match ($type->getName()) {
            'int' => 12345,
            'string' => 'テスト値',
            'bool' => true,
            default => null,
        };
    }

    /**
     * @param class-string<Entity> $class
     * @param array<string, mixed> $data
     */
    private static function newEntity(string $class, array $data): Entity
    {
        if ($class === ErrorResponse::class) {
            return new ErrorResponse(
                $data,
                new Response(
                    new Psr7Response(400),
                    new RequestMeta('POST', 'https://api.shop-pro.jp/oauth/token', []),
                ),
            );
        }

        return new $class($data);
    }
}
