<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Response;

/**
 * カラーミーショップ API サービスの基底クラス。
 *
 * protected 定数・メソッドは内部実装用 API です。利用者によるオーバーライドは想定しておらず、
 * 将来予告なく変更される場合があります。
 */
abstract class Service
{
    protected const API_BASE_URL = 'https://api.shop-pro.jp/v1';

    protected string $_accessToken;
    protected ?ClientInterface $_httpClient;

    /**
     * @param string $accessToken
     * @param ClientInterface|null $httpClient HTTP クライアント (省略時は Guzzle のデフォルト)
     * @return void
     */
    public function __construct(string $accessToken, ?ClientInterface $httpClient = null)
    {
        $this->_accessToken = $accessToken;
        $this->_httpClient = $httpClient;
    }

    /**
     * API のベース URL とパスを結合する。
     */
    protected function _endpoint(string $path): string
    {
        return \rtrim(self::API_BASE_URL, '/') . '/' . \ltrim($path, '/');
    }

    /**
     * 認証情報を設定したリクエストを生成する。
     */
    protected function _request(array $options = [], ?string $accessToken = null): Request
    {
        return new Request(new RequestOptions([
            ...$options,
            'authorization' => $accessToken ?? $this->_accessToken,
        ]), $this->_httpClient);
    }

    /**
     * API レスポンスのエラーを処理し、成功時のボディを変換する。
     *
     * @template T
     * @param Response $response API レスポンス
     * @param callable(?array): T $mapper 成功時のパース済みボディを変換する関数
     * @return T|Errors
     */
    protected function _handle(Response $response, callable $mapper): mixed
    {
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }

        return $mapper($response->getParsedBody());
    }
}
