<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Constants\SortDirection;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * API の並び順。
 *
 * 列名と昇順・降順を分けて保持する。方向を省略した場合、先頭が `-` の列名は降順、それ以外は昇順になる。
 * 列名自体は API ごとに制限せず、空文字、先頭の余分な `-`、カンマ、空白文字だけを拒否する。
 *
 * @see docs/api-product-sort-observation.md
 */
class Sort implements Value
{
    /** @var list<string>|null */
    protected const FIELDS = null;

    private readonly string $field;
    private readonly SortDirection $direction;

    /**
     * @param string $field 列名。方向省略時は先頭の `-` で降順を指定できる
     * @param SortDirection|null $direction 並び順の方向
     * @throws ParameterException 列名の形式が不正、または方向の指定が曖昧な場合
     */
    public function __construct(string $field, ?SortDirection $direction = null)
    {
        if ($direction !== null && \str_starts_with($field, '-')) {
            throw new ParameterException('方向の明示と列名先頭のハイフンは同時に指定できません。');
        }

        if ($direction === null && \str_starts_with($field, '-')) {
            $field = \substr($field, 1);
            $direction = SortDirection::DESC;
        }

        if (! self::isValidField($field)) {
            throw new ParameterException('並び順の列名の形式が不正です。');
        }

        if (static::FIELDS !== null && ! \in_array($field, static::FIELDS, true)) {
            throw new ParameterException(\sprintf(
                '並び順の列名は次のいずれかを指定してください: %s',
                \implode(', ', static::FIELDS),
            ));
        }

        $this->field = $field;
        $this->direction = $direction ?? SortDirection::ASC;
    }

    /**
     * 列名を取得する。
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * 並び順の方向を取得する。
     *
     * @return SortDirection
     */
    public function getDirection(): SortDirection
    {
        return $this->direction;
    }

    /**
     * API に送る並び順を取得する。
     *
     * @return string
     */
    public function get(): string
    {
        return $this->direction === SortDirection::DESC ? '-' . $this->field : $this->field;
    }

    /**
     * 並び順の文字列表現を検証する。
     *
     * @param mixed $value 検証する値
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        if (! \is_string($value)) {
            return false;
        }

        if (\str_starts_with($value, '-')) {
            $value = \substr($value, 1);
        }

        return self::isValidField($value)
            && (static::FIELDS === null || \in_array($value, static::FIELDS, true));
    }

    private static function isValidField(string $value): bool
    {
        return $value !== ''
            && ! \str_starts_with($value, '-')
            && ! \str_contains($value, ',')
            && \preg_match('/\s/u', $value) === 0;
    }
}
