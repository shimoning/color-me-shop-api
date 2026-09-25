<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\FallbackValue;

/** 型を問わず検証し、フォールバックの呼び出しと原因例外を観測するテストダブル。 */
final class MixedFallbackValue implements FallbackValue
{
    public static int $fallbackCalls = 0;
    public static ?ParameterException $lastException = null;

    private string $value;

    public function __construct(mixed $value)
    {
        if (! $this->validate($value)) {
            self::$lastException = new ParameterException('値が許容する形式ではありません。');
            throw self::$lastException;
        }

        $this->value = $value;
    }

    public static function fallback(string $value): static
    {
        self::$fallbackCalls++;
        $instance = new static('valid');
        $instance->value = $value;

        return $instance;
    }

    public function validate(mixed $value): bool
    {
        return $value === 'valid';
    }

    public function isValid(): bool
    {
        return $this->validate($this->value);
    }

    public function get(): string
    {
        return $this->value;
    }
}
