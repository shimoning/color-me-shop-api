<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities;

use BackedEnum;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\FallbackValue;
use Shimoning\ColorMeShopApi\Values\Value;

/**
 * API レスポンスを型付きプロパティへ変換するエンティティの基底クラス。
 */
class Entity
{
    /**
     * フィールドの型と変換方法の定義。
     *
     * プロパティ名をキーとして、子 Entity・enum・値オブジェクト、scalar、配列への変換と null の扱いを
     * 宣言する。要求では array フィールドにリストを要求し、delimiter 指定の配列は再帰配列化時に連結する。
     * API のフィールド名は FIELD_NAMES に定義する。
     *
     * @see docs/adr/0023-validate-scalar-array-elements-via-field-types.md
     * @see docs/adr/0025-reject-non-list-arrays-in-requests.md
     */
    const FIELD_TYPES = [];

    /**
     * 自動変換では表現できない API のフィールド名の対応表。
     *
     * プロパティ名をキー、API のフィールド名を値として、必要なものだけ定義する。
     *
     * @see docs/adr/0004-entity-to-array-is-not-round-trippable.md
     */
    const FIELD_NAMES = [];

    /**
     * 通常のインスタンスプロパティのキャッシュ (クラス単位)
     *
     * @var array<class-string, array<string, ReflectionProperty>>
     */
    private static array $_properties = [];

    /**
     * 子 Entity のコンストラクタへ渡す要求文脈。
     *
     * 子クラスが __construct() を上書きしていても伝わるよう、構築中だけ設定し
     * finally で復元する。RequestEntity の印がない孫にも引き継ぐ。
     */
    private static bool $_requestContext = false;

    /**
     * 要求側 Entity ごとに、利用者が明示したプロパティ名を保持する。
     *
     * 未設定と明示した `null` を区別するため、取り込み時のキー集合を記録する。
     *
     * @var array<string, true>
     */
    private array $_requestFields;

    private array $_raw;

    /**
     * API レスポンスからエンティティを生成する。
     *
     * @param array<string, mixed> $data API レスポンスデータ
     * @return void
     */
    public function __construct(array $data)
    {
        $this->_raw = $data;
        if ($this instanceof RequestEntity) {
            $this->_requestFields = [];
        }

        $fieldTypes = static::FIELD_TYPES;

        $propertyNames = \array_flip(static::FIELD_NAMES);

        foreach ($data as $key => $value) {
            $_key = $propertyNames[$key]
                ?? lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            if (self::findProperty(static::class, $_key) !== null) {
                $this->hydrateField($_key, $key, $value, $fieldTypes[$_key] ?? null);
                if ($this instanceof RequestEntity) {
                    $this->markRequestField($_key);
                }
            }
        }

        $this->initializeOptionalProperties();
    }

    /**
     * 配列で、連番のキーを持ち、要素がちょうど 2 つかを判定する。
     */
    protected static function isPairTuple(mixed $value): bool
    {
        return \is_array($value)
            && \array_is_list($value)
            && \count($value) === 2;
    }

    /**
     * フィールドが初期化済みであることを保証する。
     *
     * @throws MissingFieldException API レスポンスにフィールドが存在しない場合
     */
    protected function assertFieldInitialized(string $property): void
    {
        $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $scope = $trace[1]['class'] ?? static::class;
        if (! \is_a($scope, self::class, true)) {
            $scope = static::class;
        }

        /** @var class-string<Entity> $scope */
        $reflection = self::findProperty($scope, $property);
        if ($reflection === null || ! $reflection->isInitialized($this)) {
            throw MissingFieldException::for(static::class, static::apiFieldName($property));
        }
    }

    /**
     * API フィールドを変換・検証して宣言プロパティへ格納する。
     *
     * @param class-string|array<string, mixed>|null $objectField
     * @throws InvalidFieldException 存在する値の変換または型が不正な場合
     */
    private function hydrateField(
        string $property,
        string $apiField,
        mixed $value,
        mixed $objectField,
    ): void {
        $reflection = self::property(static::class, $property);
        $expected = self::expectedType($reflection->getType(), $reflection);
        $this->assertRequestArrayIsList($objectField, $apiField, $value, true);

        try {
            $hydrated = $objectField === null ? $value : $this->build($objectField, $value);
        } catch (\Throwable $error) {
            $elementType = self::arrayElementType($objectField);
            if ($elementType !== null && \is_array($value)) {
                throw InvalidFieldException::forArrayElement(
                    static::class,
                    $apiField,
                    $elementType,
                    $error,
                );
            }

            throw InvalidFieldException::for(static::class, $apiField, $expected, $value, $error);
        }

        if (! self::accepts($reflection->getType(), $hydrated, $reflection)) {
            throw InvalidFieldException::for(static::class, $apiField, $expected, $hydrated);
        }

        try {
            $reflection->setValue($this, $hydrated);
        } catch (\Throwable $error) {
            throw InvalidFieldException::for(
                static::class,
                $apiField,
                $expected,
                $hydrated,
                $error,
            );
        }
    }

    /**
     * 要求側で JSON object になる非リスト配列を、要素変換より先に拒否する。
     *
     * entity 配列の文字列キーは、単一 Entity の入力を配列へ包む既存形式として扱う。
     * value・enum・scalar 配列にはその互換形式がないため、文字列キーも拒否する。
     *
     * @throws InvalidFieldException 配列フィールドがリスト形状でない場合
     */
    private function assertRequestArrayIsList(
        mixed $objectField,
        string $apiField,
        mixed $value,
        bool $allowStringKeyedSingleEntity,
    ): void {
        if (
            ! ($this instanceof RequestEntity || self::$_requestContext)
            || ! \is_array($value)
            || ! \is_array($objectField)
            || empty($objectField['array'])
            || \array_is_list($value)
        ) {
            return;
        }
        if (
            $allowStringKeyedSingleEntity
            && isset($objectField['entity'])
            && empty($objectField['strictList'])
            && self::hasOnlyStringKeys($value)
        ) {
            return;
        }

        $elementType = self::arrayElementType($objectField);
        if ($elementType !== null) {
            throw InvalidFieldException::for(
                static::class,
                $apiField,
                'list<' . $elementType . '>',
                $value,
            );
        }
    }

    /**
     * setter から更新する要求配列フィールドがリスト形状であることを検証する。
     *
     * @throws InvalidFieldException 配列フィールドがリスト形状でない場合
     */
    protected function assertRequestArrayFieldIsList(string $property, mixed $value): void
    {
        $this->assertRequestArrayIsList(
            static::FIELD_TYPES[$property] ?? null,
            static::apiFieldName($property),
            $value,
            false,
        );
    }

    /** @param array<array-key, mixed> $value */
    private static function hasOnlyStringKeys(array $value): bool
    {
        return \array_filter(
            \array_keys($value),
            static fn (int|string $key): bool => ! \is_string($key),
        ) === [];
    }

    private static function arrayElementType(mixed $objectField): ?string
    {
        self::assertScalarDeclaration($objectField);
        if (! \is_array($objectField) || empty($objectField['array'])) {
            return null;
        }

        foreach (['entity', 'value', 'enum', 'scalar'] as $key) {
            if (isset($objectField[$key]) && \is_string($objectField[$key])) {
                return $objectField[$key];
            }
        }

        return null;
    }

    /**
     * @param class-string<Entity> $class
     */
    private static function property(string $class, string $property): ReflectionProperty
    {
        $reflection = self::findProperty($class, $property);
        if ($reflection !== null) {
            return $reflection;
        }

        throw MissingFieldException::for($class, $class::apiFieldName($property));
    }

    /**
     * @param class-string<Entity> $class
     */
    private static function findProperty(string $class, string $property): ?ReflectionProperty
    {
        return self::resolveProperties($class)[$property] ?? null;
    }

    /**
     * 通常のインスタンスプロパティを実行時クラスに近い宣言から解決する。
     *
     * 同名プロパティは最も近い宣言だけを採用する。最も近い宣言が契約外でも
     * 祖先の同名宣言へフォールスルーせず、その名前自体を対象外とする。
     *
     * @param class-string<Entity> $class
     * @return array<string, ReflectionProperty>
     */
    private static function resolveProperties(string $class): array
    {
        if (isset(self::$_properties[$class])) {
            return self::$_properties[$class];
        }

        $properties = [];
        $declaredNames = [];
        $reflection = new ReflectionClass($class);
        do {
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                    continue;
                }
                $name = $property->getName();
                if (isset($declaredNames[$name])) {
                    continue;
                }

                $declaredNames[$name] = true;
                if (self::isOrdinaryInstanceProperty($property)) {
                    $properties[$name] = $property;
                }
            }
            $reflection = $reflection->getParentClass();
        } while ($reflection !== false);

        return self::$_properties[$class] = $properties;
    }

    /**
     * hydrate と配列化の対象になる通常のインスタンスプロパティか判定する。
     */
    private static function isOrdinaryInstanceProperty(ReflectionProperty $property): bool
    {
        if ($property->isStatic()) {
            return false;
        }

        return ! \method_exists($property, 'isVirtual') || ! $property->isVirtual();
    }

    private static function expectedType(?ReflectionType $type, ReflectionProperty $property): string
    {
        if ($type === null) {
            return 'mixed';
        }
        if ($type instanceof ReflectionNamedType) {
            return self::resolveNamedType($type, $property);
        }
        if ($type instanceof ReflectionUnionType) {
            $types = \array_filter(
                $type->getTypes(),
                static fn(ReflectionType $nested): bool => ! ($nested instanceof ReflectionNamedType)
                    || $nested->getName() !== 'null',
            );

            return \implode('|', \array_map(
                static function (ReflectionType $nested) use ($property): string {
                    $name = self::expectedType($nested, $property);

                    return $nested instanceof ReflectionIntersectionType ? '(' . $name . ')' : $name;
                },
                $types,
            ));
        }
        if ($type instanceof ReflectionIntersectionType) {
            return \implode('&', \array_map(
                static fn(ReflectionType $nested): string => self::expectedType($nested, $property),
                $type->getTypes(),
            ));
        }

        return (string) $type;
    }

    private static function accepts(
        ?ReflectionType $type,
        mixed $value,
        ReflectionProperty $property,
    ): bool
    {
        if ($type === null) {
            return true;
        }
        if ($value === null) {
            return $type->allowsNull();
        }
        if ($type instanceof ReflectionUnionType) {
            return \array_reduce(
                $type->getTypes(),
                static fn(bool $accepted, ReflectionType $nested): bool =>
                    $accepted || self::accepts($nested, $value, $property),
                false,
            );
        }
        if ($type instanceof ReflectionIntersectionType) {
            return \array_reduce(
                $type->getTypes(),
                static fn(bool $accepted, ReflectionType $nested): bool =>
                    $accepted && self::accepts($nested, $value, $property),
                true,
            );
        }

        return self::acceptsNamedType($type, $value, $property);
    }

    private static function acceptsNamedType(
        ReflectionNamedType $type,
        mixed $value,
        ReflectionProperty $property,
    ): bool
    {
        $name = self::resolveNamedType($type, $property);
        if (! $type->isBuiltin()) {
            return $value instanceof $name;
        }

        return match ($name) {
            'array' => \is_array($value),
            'bool' => \is_bool($value),
            'callable' => \is_callable($value),
            'false' => $value === false,
            'float' => \is_float($value),
            'int' => \is_int($value),
            'iterable' => \is_iterable($value),
            'mixed' => true,
            'object' => \is_object($value),
            'string' => \is_string($value),
            'true' => $value === true,
            default => false,
        };
    }

    private static function resolveNamedType(
        ReflectionNamedType $type,
        ReflectionProperty $property,
    ): string
    {
        $name = $type->getName();
        $declaringClass = $property->getDeclaringClass();

        if ($name === 'self' || $name === 'static') {
            return $declaringClass->getName();
        }
        if ($name === 'parent') {
            $parent = $declaringClass->getParentClass();

            return $parent === false ? $name : $parent->getName();
        }

        return $name;
    }

    /**
     * レスポンスに含まれていなかった null 許容プロパティを null で初期化する
     *
     * 型付きプロパティは初期化しないまま参照すると Error になるため、
     * API が返さなかった項目のゲッターが実行時に落ちるのを防ぐ。
     *
     * @return void
     */
    private function initializeOptionalProperties(): void
    {
        foreach (self::resolveProperties(static::class) as $property) {
            if ($property->hasDefaultValue()) {
                continue;
            }
            $type = $property->getType();
            if ($type === null || ! $type->allowsNull()) {
                continue;
            }
            if (! $property->isInitialized($this)) {
                $property->setValue($this, null);
            }
        }
    }

    /**
     * オブジェクトのフィールド
     *
     * 要求側の子 Entity は未マークでも厳格な enum 検証を引き継ぐ。
     * 各子クラスへのマーカー付けに頼ると新しいネストの追加時に漏れるため。
     *
     * @param class-string|array<string, mixed> $objectField
     * @param mixed $value
     * @return mixed
     */
    protected function build(mixed $objectField, mixed $value): mixed
    {
        self::assertScalarDeclaration($objectField);
        self::assertOrScalarDeclaration($objectField);
        self::assertStrictListDeclaration($objectField);
        self::assertDelimiterDeclaration($objectField);
        if (\is_array($objectField)) {
            $isArray = !empty($objectField['array']);
            if (isset($objectField['orScalar']) && self::isScalarType($value, $objectField['orScalar'])) {
                return $value;
            }
            // nullable は falsy 値も変換するため、null だけを許すフィールドは allowNull を使う。
            if (!empty($objectField['allowNull']) && $value === null) {
                return null;
            }
            if (!empty($objectField['nullable']) && !$value) {
                return $isArray && $value !== null ? [] : null;
            }

            if (isset($objectField['entity'])) {
                $class = $objectField['entity'];
                if ($isArray) {
                    // 配列指定
                    if (static::isHash($value)) {
                        // しかし中身は連想配列
                        return [$this->buildEntity($class, $value)];
                    } else {
                        return array_map(function ($v) use ($class) {
                            return $this->buildEntity($class, $v);
                        }, $value);
                    }
                }
                return $this->buildEntity($class, $value);
            }
            if (isset($objectField['value'])) {
                $class = $objectField['value'];
                if ($isArray) {
                    // 配列指定
                    if (static::isHash($value)) {
                        // しかし中身は連想配列
                        return [$this->buildObject($class, $value)];
                    } else {
                        return array_map(function ($v) use ($class) {
                            return $this->buildObject($class, $v);
                        }, $value);
                    }
                }
                return $this->buildObject($class, $value);
            }
            if (isset($objectField['enum'])) {
                $enum = $objectField['enum'];
                if ($value === null) {
                    return null;
                }
                if ($isArray) {
                    return array_map(function ($v) use ($enum) {
                        return $this->buildEnum($enum, $v);
                    }, $value);
                }
                return $this->buildEnum($enum, $value);
            }
            if (isset($objectField['scalar'])) {
                if (! \is_array($value)) {
                    return $value;
                }

                if ($objectField['scalar'] === 'int') {
                    foreach ($value as $element) {
                        if (! \is_int($element)) {
                            throw new \TypeError('配列要素が int ではありません。');
                        }
                    }
                }
                if ($objectField['scalar'] === 'string') {
                    foreach ($value as $element) {
                        if (! \is_string($element)) {
                            throw new \TypeError('配列要素が string ではありません。');
                        }
                    }
                }

                return $value;
            }
        }

        // 単体の子 Entity をクラス名だけで宣言する従来形式。
        if (\is_string($objectField) && \is_a($objectField, self::class, true)) {
            return $this->buildEntity($objectField, $value);
        }

        return $this->buildObject($objectField, $value);
    }

    /**
     * 子 Entity の既存インスタンスは参照を保持する。
     * 要求文脈の非 RequestEntity だけは、生データから再構築する。
     *
     * @param class-string<Entity> $class
     */
    private function buildEntity(string $class, mixed $value): Entity
    {
        if (! $value instanceof $class) {
            return $this->buildObject($class, $value);
        }
        if (
            ($this instanceof RequestEntity || self::$_requestContext)
            && ! ($value instanceof RequestEntity)
        ) {
            return $this->buildObject($class, $value->getRaw());
        }

        return $value;
    }

    private static function assertScalarDeclaration(mixed $objectField): void
    {
        if (! \is_array($objectField) || ! \array_key_exists('scalar', $objectField)) {
            return;
        }
        if (! \in_array($objectField['scalar'], ['int', 'string'], true)) {
            throw new \LogicException('scalar は int または string を指定してください。');
        }
        if (empty($objectField['array'])) {
            throw new \LogicException('scalar は array と組み合わせて指定してください。');
        }
    }

    private static function assertOrScalarDeclaration(mixed $objectField): void
    {
        if (! \is_array($objectField) || ! \array_key_exists('orScalar', $objectField)) {
            return;
        }
        if (! \in_array($objectField['orScalar'], ['int', 'string'], true)) {
            throw new \LogicException('orScalar は int または string を指定してください。');
        }
        if (! isset($objectField['entity'])) {
            throw new \LogicException('orScalar は entity と組み合わせて指定してください。');
        }
        if (! empty($objectField['array'])) {
            throw new \LogicException('orScalar は array と組み合わせて指定できません。');
        }
    }

    private static function assertStrictListDeclaration(mixed $objectField): void
    {
        if (! \is_array($objectField) || ! \array_key_exists('strictList', $objectField)) {
            return;
        }
        if ($objectField['strictList'] !== true) {
            throw new \LogicException('strictList は true を指定してください。');
        }
        if (($objectField['array'] ?? null) !== true) {
            throw new \LogicException('strictList は array => true と組み合わせて指定してください。');
        }
        if (! isset($objectField['entity'])) {
            throw new \LogicException('strictList は entity と組み合わせて指定してください。');
        }
    }

    private static function assertDelimiterDeclaration(mixed $objectField): void
    {
        if (! \is_array($objectField) || ! \array_key_exists('delimiter', $objectField)) {
            return;
        }
        if (! \is_string($objectField['delimiter']) || $objectField['delimiter'] === '') {
            throw new \LogicException('delimiter は空でない文字列を指定してください。');
        }
        if (($objectField['array'] ?? null) !== true) {
            throw new \LogicException('delimiter は array => true と組み合わせて指定してください。');
        }
    }

    private static function isScalarType(mixed $value, string $scalar): bool
    {
        return match ($scalar) {
            'int' => \is_int($value),
            'string' => \is_string($value),
            default => false,
        };
    }

    /**
     * FIELD_TYPES の子オブジェクトを親と同じ要求文脈で構築する。
     *
     * @param class-string<object> $class
     */
    private function buildObject(string $class, mixed $value): object
    {
        $previous = self::$_requestContext;
        self::$_requestContext = $previous || $this instanceof RequestEntity;
        try {
            return new $class($value);
        } catch (ParameterException $exception) {
            $strict = $this instanceof RequestEntity || self::$_requestContext;
            // 明示的に対応した値だけ、応答側の検証失敗時に生値を保持する。
            if (! $strict && \is_subclass_of($class, FallbackValue::class) && \is_string($value)) {
                return $class::fallback($value);
            }
            throw $exception;
        } finally {
            self::$_requestContext = $previous;
        }
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    private function buildEnum(string $enum, mixed $value): BackedEnum
    {
        // 同じ enum のインスタンスはバッキング値と同じ扱いにする (要求側の番兵拒否は下で共通に適用する)。
        $case = $value instanceof $enum ? $value : $enum::tryFrom($value);
        if (\is_subclass_of($enum, FallbackEnum::class)) {
            $strict = $this instanceof RequestEntity || self::$_requestContext;
            if ($strict && $case === $enum::fallbackCase()) {
                throw new \UnexpectedValueException('未知の enum 値です。');
            }
            if ($case === null && ! $strict) {
                return $enum::fallbackCase();
            }
        }

        if ($case === null) {
            throw new \UnexpectedValueException('未知の enum 値です。');
        }

        return $case;
    }

    /**
     * プロパティ名に対応する API のフィールド名を取得する
     *
     * @param string $property
     * @return string
     */
    public static function apiFieldName(string $property): string
    {
        return static::FIELD_NAMES[$property]
            ?? ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $property)), '_');
    }

    /**
     * 配列化の対象外とする内部プロパティかどうか
     *
     * 生データの保持やリフレクション結果のキャッシュに使うプロパティは
     * アンダースコアで始める規約とし、配列化の対象から外す。
     *
     * @param string $name
     * @return bool
     */
    private static function isInternalProperty(string $name): bool
    {
        return \str_starts_with($name, '_');
    }

    /**
     * 連想配列かどうか
     * @param array $array
     * @return bool
     */
    public static function isHash(array $array): bool
    {
        foreach ($array as $key => $_) {
            if (\is_string($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 配列として取得する。
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [];
        foreach (self::resolveProperties(static::class) as $key => $property) {
            if (self::isInternalProperty($key)) {
                continue;
            }
            $_key = static::apiFieldName($key);
            $array[$_key] = $property->isInitialized($this)
                ? $property->getValue($this)
                : null;
        }
        return $array;
    }

    /**
     * 再帰的に配列として取得する。
     *
     * 応答の未知の FallbackEnum 値は番兵の backing value になる。
     * 元の API 値は getRaw() に残るため、両者は一致しない場合がある。
     *
     * @param bool $ignoreNull null の値を除外するか
     * @return array<string, mixed>
     * @see docs/adr/0009-opt-in-enum-fallback.md
     * @see docs/adr/0013-expand-opt-in-enum-fallback.md
     */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $array = [];
        $request = $this instanceof RequestEntity;
        $requestFields = $request ? $this->requestFields() : null;
        foreach (self::resolveProperties(static::class) as $key => $property) {
            if (self::isInternalProperty($key)) {
                continue;
            }
            $fieldType = static::FIELD_TYPES[$key] ?? null;
            self::assertDelimiterDeclaration($fieldType);
            if ($request && $requestFields !== null && ! isset($requestFields[$key])) {
                continue;
            }
            $_key = static::apiFieldName($key);
            $value = $property->isInitialized($this)
                ? $property->getValue($this)
                : null;
            if ((! $request || $requestFields === null) && $ignoreNull && $value === null) {
                continue;
            }
            if (\is_array($value)) {
                if (
                    \is_array($fieldType)
                    && \array_key_exists('delimiter', $fieldType)
                    && $value === []
                ) {
                    continue;
                }
                $value = array_map([$this, 'parse'], $value);
                if (\is_array($fieldType) && \array_key_exists('delimiter', $fieldType)) {
                    $value = \implode($fieldType['delimiter'], $value);
                }
            } else {
                $value = $this->parse($value);
            }
            $array[$_key] = $value;
        }
        return $array;
    }

    /**
     * setter 経由で明示された要求フィールドを記録する。
     */
    protected function markRequestField(string $property): void
    {
        if (! isset($this->_requestFields)) {
            $this->_requestFields = [];
            foreach (self::resolveProperties(static::class) as $key => $reflection) {
                if (
                    ! self::isInternalProperty($key)
                    && $reflection->isInitialized($this)
                    && $reflection->getValue($this) !== null
                ) {
                    $this->_requestFields[$key] = true;
                }
            }
        }
        $this->_requestFields[$property] = true;
    }

    /**
     * @return array<string, true>|null null は明示フィールド追跡追加前の直列化データ
     */
    private function requestFields(): ?array
    {
        return isset($this->_requestFields) ? $this->_requestFields : null;
    }

    /**
     * 配列化に適した値へ変換する。
     *
     * @param mixed $value 変換する値
     * @return mixed
     */
    public function parse(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArrayRecursive();
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof Value) {
            return $value->get();
        }

        return $value;
    }

    /**
     * 変換前の生データをそのまま取得する。
     *
     * 応答の未知の FallbackEnum 値も元値のまま残る。
     * toArrayRecursive() は番兵の backing value を返す。
     *
     * @return array
     * @see docs/adr/0009-opt-in-enum-fallback.md
     * @see docs/adr/0013-expand-opt-in-enum-fallback.md
     */
    public function getRaw(): array
    {
        return $this->_raw;
    }
}
