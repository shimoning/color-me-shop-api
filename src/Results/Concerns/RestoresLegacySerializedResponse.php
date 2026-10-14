<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Results\Concerns;

/**
 * 移動前のクラス名で直列化された Response 保持結果を復元する。
 *
 * @internal
 */
trait RestoresLegacySerializedResponse
{
    /**
     * 移動前の private キーを現行名へ読み替えてから、直列化されたプロパティを復元する。
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $legacyResponseKey = "\0" . self::LEGACY_SERIALIZED_CLASS . "\0_response";
        $currentResponseKey = "\0" . self::class . "\0_response";

        if (array_key_exists($legacyResponseKey, $data)) {
            if (! \array_key_exists($currentResponseKey, $data)) {
                $data[$currentResponseKey] = $data[$legacyResponseKey];
            }
            unset($data[$legacyResponseKey]);
        }

        foreach ($data as $key => $value) {
            [$scope, $propertyName] = self::serializedProperty($key);
            $property = self::declaredProperty($scope, $propertyName);
            if ($property !== null) {
                $property->setValue($this, $value);
                continue;
            }

            // 宣言がなくなった旧プロパティは PHP 標準と同じく動的プロパティとして復元する。
            // PHP 8.2 以降の E_DEPRECATED は意図的に抑止しない。
            $this->{$propertyName} = $value;
        }
    }

    /** @return array{string|null, string} */
    private static function serializedProperty(string $key): array
    {
        if (! \str_starts_with($key, "\0")) {
            return [null, $key];
        }

        $parts = \explode("\0", $key, 3);
        if (\count($parts) !== 3 || $parts[2] === '') {
            throw new \UnexpectedValueException('不正な serialize プロパティです。');
        }

        return [$parts[1], $parts[2]];
    }

    private function declaredProperty(?string $scope, string $name): ?\ReflectionProperty
    {
        if ($scope !== null && $scope !== '*') {
            if (! \is_a($this, $scope)) {
                return null;
            }

            try {
                return new \ReflectionProperty($scope, $name);
            } catch (\ReflectionException) {
                return null;
            }
        }

        try {
            return new \ReflectionProperty($this, $name);
        } catch (\ReflectionException) {
            return null;
        }
    }
}
