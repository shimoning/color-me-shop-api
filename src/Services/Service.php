<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;

/**
 * カラーミーショップ API サービスの基底クラス。
 *
 * protected 定数・メソッドは内部実装用 API です。利用者によるオーバーライドは想定しておらず、
 * 将来予告なく変更される場合があります。
 */
abstract class Service
{
    protected const _API_BASE_URL = 'https://api.shop-pro.jp/v1';

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
        return \rtrim(self::_API_BASE_URL, '/') . '/' . \ltrim($path, '/');
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
}
