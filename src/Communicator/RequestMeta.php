<?php

namespace Shimoning\ColorMeShopApi\Communicator;

/**
 * 実行した HTTP リクエストのメタデータ。
 * multipart の stream resource は保持せず、filename と size のスナップショットを保持する。
 */
class RequestMeta
{
    private string $_method;
    private string $_uri;
    private array $_options;

    /**
     * リクエストメタデータを生成する。
     *
     * @param string $method HTTP メソッド
     * @param string $uri リクエスト URI
     * @param array<string, mixed> $options HTTP クライアントへ渡したオプションの安全なスナップショット
     * @return void
     */
    public function __construct(
        string $method,
        string $uri,
        array $options,
    ) {
        $this->_method = $method;
        $this->_uri = $uri;
        $this->_options = $options;
    }

    /**
     * HTTP メソッドを取得する。
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->_method;
    }
    /**
     * リクエスト URI を取得する。
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->_uri;
    }
    /**
     * HTTP クライアントへ渡したオプションの安全なスナップショットを取得する。
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->_options;
    }
}
