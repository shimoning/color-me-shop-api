<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * フリガナ (カタカナ)
 *
 * 許容する文字は `^[ァ-ヶー 　]*$` とし、空文字も受け付ける。
 * 管理画面経由の不正な応答値は生の文字列を保持し、isValid() は false を返す。
 *
 * 公式 OpenAPI との差分: 許容される ヷヸヹヺ を実 API は 422 で拒否する (2026-09-25)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-customer-structure.md
 */
class Furigana implements FallbackValue
{
    private string $_furigana;

    /**
     * コンストラクタの検証を通さず、API 応答の生値を保持する。
     * ReflectionClass::newInstanceWithoutConstructor() を使うため、
     * サブクラスのコンストラクタも省略される。
     * 状態を追加するサブクラスは fallback() を上書きし、その状態を初期化する必要がある。
     */
    public static function fallback(string $value): static
    {
        $instance = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
        $instance->_furigana = $value;

        return $instance;
    }

    /** 保持している値が許容するフリガナの形式に一致するかを返す。 */
    public function isValid(): bool
    {
        return $this->validate($this->_furigana);
    }

    /**
     * @param string $furigana
     * @return void
     * @throws ParameterException フリガナの形式が不正な場合
     */
    public function __construct(string $furigana)
    {
        if (! $this->validate($furigana)) {
            throw new ParameterException('フリガナはカタカナで入力してください。 : ' . $furigana);
        }
        $this->_furigana = $furigana;
    }

    /**
     * 取得
     * @return string
     */
    public function get(): string
    {
        return $this->_furigana;
    }

    /**
     * バリデーション
     *
     * @param mixed $value
     * @return boolean
     */
    public function validate(mixed $value): bool
    {
        return preg_match('/\A[ァ-ヶー 　]*\z/u', $value) === 1;
    }
}
