<?php

namespace Shimoning\ColorMeShopApi\Entities;

class Entity
{
    private array $_raw;

    public function __construct(array $data)
    {
        $this->_raw = $data;

        $objectFields = defined((static::class) . '::OBJECT_FIELDS') ? static::OBJECT_FIELDS : [];

        foreach ($data as $key => $value) {
            $_key = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            if (property_exists($this, $_key)) {
                if (isset($objectFields[$_key])) {
                    $this->{$_key} = $this->getObjectFieldValue($objectFields[$_key], $value);
                    continue;
                }
                $this->{$_key} = $value;
            }
        }
    }

    /**
     * オブジェクトのフィールド
     *
     * @param string|array $objectField
     * @param mixed $value
     * @return array|object
     */
    protected function getObjectFieldValue(mixed $objectField, mixed $value): mixed
    {
        if (\is_array($objectField) && isset($objectField['entity'])) {
            $entity = $objectField['entity'];
            if (!empty($objectField['array'])) {
                // 配列指定
                if (static::isHash($value)) {
                    // しかし中身は連想配列
                    return [new $entity($value)];
                } else {
                    return array_map(function ($v) use ($entity) {
                        return new $entity($v);
                    }, $value);
                }
            } else if (!empty($objectField['enum'])) {
                return $entity::tryFrom($value);
            } else {
                // 単体指定
                return new $entity($value);
            }
        }

        // 単体
        return new $objectField($value);
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
     * @return array
     */
    public function toArray(): array
    {
        $properties = get_class_vars(static::class);

        $array = [];
        foreach ($properties as $key => $value) {
            $_key = ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $key)), '_');
            $array[$_key] = $value;
        }
        return $array;
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
