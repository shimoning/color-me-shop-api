<?php

namespace Shimoning\ColorMeShopApi\Communicator;

/**
 * HTTP リクエストのオプション。
 */
class RequestOptions
{
    private float $_timeout = 0;
    private float $_connectTimeout = 0;
    private bool $_form = false;
    private bool $_json = false;
    private ?string $_authorization = null;

    /**
     * リクエストオプションを生成する。
     *
     * @param array{timeout?: float|int|string, connect_timeout?: float|int|string, form?: bool, json?: bool, authorization?: string}|null $options
     * @return void
     */
    public function __construct(?array $options = [])
    {
        if (isset($options['timeout'])) {
            $this->_timeout = (float)$options['timeout'];
        }
        if (isset($options['connect_timeout'])) {
            $this->_connectTimeout = (float)$options['connect_timeout'];
        }
        if (isset($options['form'])) {
            $this->_form = (bool)$options['form'];
        }
        if (isset($options['json'])) {
            $this->_json = (bool)$options['json'];
        }
        if (isset($options['authorization'])) {
            $authorization = \strpos($options['authorization'], 'Bearer ') === 0
                ? $options['authorization']
                : 'Bearer ' . $options['authorization'];
            $this->_authorization = $authorization;
        }
    }

    /**
     * リクエスト全体のタイムアウト秒数を取得する。
     *
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->_timeout;
    }
    /**
     * 接続タイムアウト秒数を取得する。
     *
     * @return float
     */
    public function getConnectTimeout(): float
    {
        return $this->_connectTimeout;
    }
    /**
     * フォーム形式で送信するかを判定する。
     *
     * @return bool
     */
    public function isForm(): bool
    {
        return $this->_form;
    }
    /**
     * JSON 形式で送信するかを判定する。
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->_json;
    }
    /**
     * Authorization ヘッダーの値を取得する。
     *
     * @return string|null
     */
    public function getAuthorization(): ?string
    {
        return $this->_authorization;
    }
}
