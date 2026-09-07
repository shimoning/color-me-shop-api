<?php

namespace Shimoning\ColorMeShopApi\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle の MockHandler と History ミドルウェアをまとめた、
 * 実際に送信されたリクエストを検証するためのテストヘルパー。
 *
 * 外部への通信は一切発生しない。
 */
class HttpMock
{
    private Client $_client;

    /** @var array<array{request: RequestInterface}> */
    private array $_history = [];

    /**
     * @param array<Psr7Response|\Throwable> $responses 順番に返されるレスポンス
     */
    public function __construct(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->_history));

        $this->_client = new Client(['handler' => $stack]);
    }

    /**
     * JSON を1件返すモックを作る
     */
    public static function json(int $status, string $body, array $headers = []): self
    {
        return new self([new Psr7Response($status, $headers + ['Content-Type' => 'application/json'], $body)]);
    }

    public function client(): Client
    {
        return $this->_client;
    }

    /**
     * 送信されたリクエストの件数
     */
    public function countRequests(): int
    {
        return \count($this->_history);
    }

    /**
     * 送信された n 番目 (0 始まり) のリクエスト
     */
    public function request(int $index = 0): RequestInterface
    {
        if (! isset($this->_history[$index])) {
            throw new \OutOfRangeException(
                \sprintf('%d 番目のリクエストは送信されていない (送信件数: %d)', $index, $this->countRequests()),
            );
        }
        return $this->_history[$index]['request'];
    }

    /**
     * Guzzle に実際に渡されたリクエストオプション
     *
     * ヘッダではなくオプションとして渡すべき設定 (timeout など) の検証に使う。
     */
    public function options(int $index = 0): array
    {
        if (! isset($this->_history[$index])) {
            throw new \OutOfRangeException(
                \sprintf('%d 番目のリクエストは送信されていない (送信件数: %d)', $index, $this->countRequests()),
            );
        }
        return $this->_history[$index]['options'];
    }

    /**
     * 送信されたリクエストの URI (クエリ文字列を含む)
     */
    public function uri(int $index = 0): string
    {
        return (string)$this->request($index)->getUri();
    }

    /**
     * 送信されたリクエストのクエリ文字列を配列にパースしたもの
     */
    public function query(int $index = 0): array
    {
        \parse_str($this->request($index)->getUri()->getQuery(), $query);
        return $query;
    }

    /**
     * 送信されたリクエストボディ
     */
    public function body(int $index = 0): string
    {
        return (string)$this->request($index)->getBody();
    }

    /**
     * 送信されたリクエストボディを JSON としてパースしたもの
     *
     * JSON であることを前提としたヘルパーのため、パースに失敗した場合は
     * null を返さずに JsonException を投げる。
     *
     * @throws \JsonException
     */
    public function jsonBody(int $index = 0): mixed
    {
        return \json_decode($this->body($index), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * 送信されたリクエストヘッダの値 (1行に結合したもの)
     */
    public function header(string $name, int $index = 0): ?string
    {
        $request = $this->request($index);
        return $request->hasHeader($name) ? $request->getHeaderLine($name) : null;
    }
}
