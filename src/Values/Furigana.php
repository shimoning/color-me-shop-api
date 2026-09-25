<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * フリガナ (カタカナ)
 *
 * 許容する文字は `^[ァ-ヶー 　]*$` とし、空文字も受け付ける。
 * 公式 OpenAPI のパターンは ヷヸヹヺ を含むが、2026-09-25 の実測で
 * 実 API が422で拒否したため除外している。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Furigana implements Value
{
    private string $_furigana;

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
