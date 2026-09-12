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
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Values\Value;

/**
 * API レスポンスを型付きプロパティへ変換するエンティティの基底クラス。
 */
class Entity
{
    /**
     * オブジェクトに変換するフィールドの定義。
     * 変換が必要なサブクラスで上書きする。
     */
    const OBJECT_FIELDS = [];

    /**
     * 自動変換では表現できない API のフィールド名の対応表。
     *
     * プロパティ名をキー、API のフィールド名を値として、必要なものだけ定義する。
     * 取り込み時は「アンダースコア区切り → camelCase」、配列化時は「大文字の前に
     * アンダースコアを挿入」という変換を行うが、この2つは対称ではない。
     * 例えば shop_mail_1 は shopMail1 として取り込まれるが、配列化すると
     * shop_mail1 となり元のフィールド名に戻らない。そうした項目をここで補う。
     */
    const FIELD_NAMES = [];

    /**
     * null 許容かつ既定値を持たないプロパティ名のキャッシュ (クラス単位)
     *
     * @var array<string, array<string>>
     */
    private static array $_optionalProperties = [];

    /**
     * 宣言プロパティのキャッシュ (クラス・プロパティ単位)
     *
     * @var array<class-string, array<string, ReflectionProperty>>
     */
    private static array $_properties = [];

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

        $objectFields = static::OBJECT_FIELDS;

        $propertyNames = \array_flip(static::FIELD_NAMES);

        foreach ($data as $key => $value) {
            $_key = $propertyNames[$key]
                ?? lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            if (self::findProperty(static::class, $_key) !== null) {
                $this->hydrateField($_key, $key, $value, $objectFields[$_key] ?? null);
            }
        }

        $this->initializeOptionalProperties();
    }

    /**
     * フィールドが初期化済みであることを保証する。
     *
     * @throws MissingFieldException API レスポンスにフィールドが存在しない場合
     */
    protected function assertFieldInitialized(string $property): void
    {
        if (! self::property(static::class, $property)->isInitialized($this)) {
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

        $reflection->setValue($this, $hydrated);
    }

    private static function arrayElementType(mixed $objectField): ?string
    {
        if (! \is_array($objectField) || empty($objectField['array'])) {
            return null;
        }

        foreach (['entity', 'value', 'enum'] as $key) {
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
        if (isset(self::$_properties[$class][$property])) {
            return self::$_properties[$class][$property];
        }

        $reflection = new ReflectionClass($class);
        do {
            if ($reflection->hasProperty($property)) {
                return self::$_properties[$class][$property] = $reflection->getProperty($property);
            }
            $reflection = $reflection->getParentClass();
        } while ($reflection !== false);

        return null;
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
        foreach ($this->optionalPropertyNames() as $name) {
            if (! isset($this->{$name})) {
                $this->{$name} = null;
            }
        }
    }

    /**
     * null 許容かつ既定値を持たないプロパティ名を取得する
     *
     * リフレクションの結果はクラス単位でキャッシュする。
     *
     * @return array<string>
     */
    private function optionalPropertyNames(): array
    {
        if (isset(self::$_optionalProperties[static::class])) {
            return self::$_optionalProperties[static::class];
        }

        $names = [];
        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            if ($property->isStatic() || $property->hasDefaultValue()) {
                continue;
            }
            // 基底クラス以外で宣言された private プロパティはここからは代入できない
            if ($property->isPrivate() && $property->getDeclaringClass()->getName() !== self::class) {
                continue;
            }
            $type = $property->getType();
            if ($type === null || ! $type->allowsNull()) {
                continue;
            }
            $names[] = $property->getName();
        }

        return self::$_optionalProperties[static::class] = $names;
    }

    /**
     * オブジェクトのフィールド
     *
     * @param class-string|array<string, mixed> $objectField
     * @param mixed $value
     * @return mixed
     */
    protected function build(mixed $objectField, mixed $value): mixed
    {
        if (\is_array($objectField)) {
            $isArray = !empty($objectField['array']);
            if (!empty($objectField['nullable']) && !$value) {
                return $isArray ? [] : null;
            }

            if (isset($objectField['entity'])) {
                $class = $objectField['entity'];
                if ($isArray) {
                    // 配列指定
                    if (static::isHash($value)) {
                        // しかし中身は連想配列
                        return [new $class($value)];
                    } else {
                        return array_map(function ($v) use ($class) {
                            return new $class($v);
                        }, $value);
                    }
                }
                return new $class($value);
            }
            if (isset($objectField['value'])) {
                $class = $objectField['value'];
                if ($isArray) {
                    // 配列指定
                    if (static::isHash($value)) {
                        // しかし中身は連想配列
                        return [new $class($value)];
                    } else {
                        return array_map(function ($v) use ($class) {
                            return new $class($v);
                        }, $value);
                    }
                }
                return new $class($value);
            }
            if (isset($objectField['enum'])) {
                $enum = $objectField['enum'];
                if ($value === null) {
                    return null;
                }
                if ($isArray) {
                    return array_map(function ($v) use ($enum) {
                        return self::buildEnum($enum, $v);
                    }, $value);
                }
                return self::buildEnum($enum, $value);
            }
        }

        // 単体
        return new $objectField($value);
    }

    /**
     * @param class-string<BackedEnum> $enum
     */
    private static function buildEnum(string $enum, mixed $value): BackedEnum
    {
        $case = $enum::tryFrom($value);
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
     * 配列として取得する
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $properties = get_class_vars(static::class);
        $values = get_object_vars($this);

        $array = [];
        foreach ($properties as $key => $_) {
            if (self::isInternalProperty($key)) {
                continue;
            }
            $_key = static::apiFieldName($key);
            $array[$_key] = $values[$key] ?? null;
        }
        return $array;
    }

    /**
     * 配列として取得する
     * @param bool $ignoreNull null の値を除外するか
     * @return array<string, mixed>
     */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $properties = get_class_vars(static::class);
        $values = get_object_vars($this);

        $array = [];
        foreach ($properties as $key => $_) {
            if (self::isInternalProperty($key)) {
                continue;
            }
            $_key = static::apiFieldName($key);
            $value = $values[$key] ?? null;
            if ($ignoreNull && $value === null) {
                continue;
            }
            if (\is_array($value)) {
                $value = array_map([$this, 'parse'], $value);
            } else {
                $value = $this->parse($value);
            }
            $array[$_key] = $value;
        }
        return $array;
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
     * 生データをそのまま取得する
     * @return array
     */
    public function getRaw(): array
    {
        return $this->_raw;
    }
}
