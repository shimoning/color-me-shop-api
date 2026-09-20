<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * カラーミーショップ API への HTTP リクエストを実行するクライアント。
 */
class Request
{
    private RequestOptions $_options;
    private ClientInterface $_client;

    /**
     * @param RequestOptions|null $options リクエストオプション (省略時は既定値)
     * @param ClientInterface|null $client HTTP クライアント (省略時は Guzzle のデフォルト)
     * @return void
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
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
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
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function post(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->sendRequest('POST', $uri, $headers, $data);
    }

    /**
     * PUT リクエスト
     * 更新
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function put(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->sendRequest('PUT', $uri, $headers, $data);
    }

    /**
     * DELETE リクエスト。ボディは送信しない。
     *
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function delete(string $uri, array $headers = []): Response
    {
        return $this->sendRequest('DELETE', $uri, $headers);
    }

    /**
     * multipart/form-data の POST リクエスト。
     *
     * @param array<string, mixed> $fields 通常フィールド
     * @param array<string, string|resource|StreamInterface> $files フィールド名 => ファイルパスまたはストリーム
     * @throws ParameterException ファイルが存在しないか、ストリームを読み取れない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function postMultipart(
        string $uri,
        array $fields,
        array $files,
        array $headers = [],
    ): Response {
        $multipart = [];
        foreach ($fields as $name => $value) {
            $multipart[] = [
                'name' => $name,
                'contents' => \is_scalar($value) || $value === null ? (string) $value : $value,
            ];
        }
        foreach ($files as $name => $file) {
            $part = [
                'name' => $name,
                'contents' => self::readableFile($name, $file),
            ];
            if (\is_string($file)) {
                $part['filename'] = \basename($file);
            }
            $multipart[] = $part;
        }

        return $this->sendRequest('POST', $uri, $headers, null, ['multipart' => $multipart]);
    }

    /**
     * @return resource|StreamInterface
     * @throws ParameterException
     */
    private static function readableFile(string $field, mixed $file): mixed
    {
        if ($file instanceof StreamInterface) {
            if (! $file->isReadable()) {
                throw new ParameterException(
                    \sprintf('multipart のフィールド『%s』は読み取り可能なストリームを指定してください。', $field),
                );
            }

            return $file;
        }

        if (\is_resource($file)) {
            $meta = \get_resource_type($file) === 'stream' ? \stream_get_meta_data($file) : [];
            $mode = $meta['mode'] ?? '';
            if (! \is_string($mode) || \preg_match('/[r+]/', $mode) !== 1) {
                throw new ParameterException(
                    \sprintf('multipart のフィールド『%s』は読み取り可能なストリームを指定してください。', $field),
                );
            }

            return $file;
        }

        if (! \is_string($file) || ! \is_file($file) || ! \is_readable($file)) {
            throw new ParameterException(
                \sprintf('multipart のフィールド『%s』は読み取り可能なファイルを指定してください。', $field),
            );
        }

        $stream = @\fopen($file, 'rb');
        if ($stream === false) {
            throw new ParameterException(
                \sprintf('multipart のフィールド『%s』は読み取り可能なファイルを指定してください。', $field),
            );
        }

        return $stream;
    }

    /**
     * リクエストを実行する
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param string|array|null $data
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    protected function sendRequest(
        string $method,
        string $uri,
        array $headers = [],
        $data = null,
        array $requestOptions = [],
    ): Response
    {
        // ヘッダを組み立てる前に Content-Type を補完する
        // (呼び出し側が明示している場合はそちらを優先する)
        // HTTP ヘッダ名は大小文字を区別しないため、判定も大小文字を無視して行う。
        // 表記違いを取りこぼすと既定値が追加され、値が2つ並んだ不正なヘッダになる
        $lowerCaseHeaderNames = \array_change_key_case($headers, \CASE_LOWER);
        if (
            ! isset($requestOptions['multipart'])
            && ! isset($lowerCaseHeaderNames['content-type'])
            && ($method === 'POST' || $method === 'PUT')
        ) {
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
            ...$requestOptions,
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
