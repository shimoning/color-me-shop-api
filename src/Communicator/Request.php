<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use GuzzleHttp\Client;

class Request
{
    private RequestOptions $_options;
    private Client $_client;

    public function __construct(?RequestOptions $options = new RequestOptions)
    {
        $this->_options = $options;
        $this->_client = new Client();
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
        $options = [
            'http_errors' => false,
            'headers' => [
                ...$this->headers(),
                ...$headers,
            ],
        ];
        if (!isset($headers['Content-Type']) && ($method === 'POST' || $method === 'PUT')) {
            if ($this->_options->isForm()) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            } else if ($this->_options->isJson()) {
                $headers['Content-Type'] = 'application/json; charset=utf-8';
            }
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
            'timeout' => $this->_options->getTimeout(),
            'connect_timeout' => $this->_options->getConnectTimeout(),
        ];
        if ($this->_options->getAuthorization()) {
            $headers['Authorization'] = $this->_options->getAuthorization();
        }
        return $headers;
    }
}
