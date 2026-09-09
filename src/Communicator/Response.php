<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use Psr\Http\Message\ResponseInterface;

/**
 * HTTP レスポンスとリクエスト情報を保持するオブジェクト。
 */
class Response
{
    private RequestMeta $_requestMeta;
    private array $_rawHeader = [];
    private string $_rawBody = '';
    private ?array $_parsedBody;
    private int $_status;

    /**
     * @param ResponseInterface $response PSR-7 レスポンス
     * @param RequestMeta $requestMeta リクエストメタデータ
     * @return void
     */
    public function __construct(
        ResponseInterface $response,
        RequestMeta $requestMeta,
    ) {
        $this->_requestMeta = $requestMeta;
        $this->_rawHeader = $response->getHeaders();
        $this->_status = $response->getStatusCode();

        $body = $response->getBody()->getContents() ?? '';
        $this->_rawBody = $body;
        $this->_parsedBody = $this->parse($body);
    }

    /**
     * ボディをパースする
     * @param string $body
     * @return ?array
     */
    private function parse(string $body): ?array
    {
        // $utf8Body = \mb_convert_encoding($body, 'UTF-8', 'SJIS');
        return \json_decode($body, true);
    }

    /**
     * 生レスポンスヘッダ
     * @return array
     */
    public function getRawHeader(): array
    {
        return $this->_rawHeader;
    }

    /**
     * 生レスポンスボディ
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->_rawBody;
    }

    /**
     * パース後のボディ
     * @return array|null
     */
    public function getParsedBody(): ?array
    {
        return $this->_parsedBody;
    }

    /**
     * HTTP ステータス
     * @return int
     */
    public function getStatus(): int
    {
        return $this->_status;
    }

    /**
     * リクエストの成否を取得
     * @return bool
     */
    public function isSuccess(): bool
    {
        return 200 <= $this->_status && $this->_status < 300;
    }

    /**
     * リクエストメタデータを取得する。
     *
     * @return RequestMeta
     */
    public function getRequestMeta(): RequestMeta
    {
        return $this->_requestMeta;
    }
}
