<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class Request
{
    private RequestOptions $_options;
    private ClientInterface $_client;

    /**
     * @param RequestOptions|null $options リクエストオプション (省略時は既定値)
     * @param ClientInterface|null $client HTTP クライアント (省略時は Guzzle のデフォルト)
     */
    public function __construct(
        ?RequestOptions $options = null,
        ?ClientInterface $client = null,
    ) {
        $this->_options = $options ?? new RequestOptions();
        $this->_client = $client ?? new Client();
    }

    /**
     * GET リクエスト
     * 取得
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return Response
     */
    public function get(string $uri, array $data = [], array $headers = []): Response
    {
        if ($data) {
            $uri .= '?' . \http_build_query($data);
        }
        return $this->sendRequest('GET', $uri, $headers);
    }

    /**
     * POST リクエスト
     * 新規作成
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return Response
     */
    public function post(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->sendRequest('POST', $uri, $headers, $data);
    }

    /**
     * PUT リクエスト
     * 新規作成
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return Response
     */
    public function put(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->sendRequest('PUT', $uri, $headers, $data);
    }

    /**
     * リクエストを実行する
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param string|array|null
     * @return Response
     */
    protected function sendRequest(string $method, string $uri, array $headers = [], $data = null): Response
    {
        // ヘッダを組み立てる前に Content-Type を補完する
        // (呼び出し側が明示している場合はそちらを優先する)
        // HTTP ヘッダ名は大小文字を区別しないため、判定も大小文字を無視して行う。
        // 表記違いを取りこぼすと既定値が追加され、値が2つ並んだ不正なヘッダになる
        $lowerCaseHeaderNames = \array_change_key_case($headers, \CASE_LOWER);
        if (!isset($lowerCaseHeaderNames['content-type']) && ($method === 'POST' || $method === 'PUT')) {
            if ($this->_options->isForm()) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            } else if ($this->_options->isJson()) {
                $headers['Content-Type'] = 'application/json; charset=utf-8';
            }
        }

        $options = [
            'http_errors' => false,
            'headers' => [
                ...$this->headers(),
                ...$headers,
            ],
        ];
        // 既定値の 0 は「未設定」を意味するため、クライアント側の設定を
        // 上書きしないよう Guzzle のオプションには含めない
        if ($this->_options->getTimeout() > 0) {
            $options['timeout'] = $this->_options->getTimeout();
        }
        if ($this->_options->getConnectTimeout() > 0) {
            $options['connect_timeout'] = $this->_options->getConnectTimeout();
        }
        if (!empty($data)) {
            if ($this->_options->isForm()) {
                $options['form_params'] = $data;
            } else if ($this->_options->isJson()) {
                $options['json'] = $data;
            } else {
                $options['body'] = $data;
            }
        }

        // リクエスト
        $response = $this->_client->request(
            $method,
            $uri,
            $options,
        );

        // レスポンスを返す
        return new Response(
            $response,
            new RequestMeta($method, $uri, $options),
        );
    }

    /**
     * 基本のヘッダ設定
     *
     * @return array
     */
    protected function headers(): array
    {
        $headers = [
            'User-Agent' => 'Shimoning ColorMeShopApi Client',
        ];
        if ($this->_options->getAuthorization()) {
            $headers['Authorization'] = $this->_options->getAuthorization();
        }
        return $headers;
    }
}
