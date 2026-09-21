<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

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
     * @throws ParameterException アクセストークンが空文字の場合
     */
    public function __construct(string $accessToken, ?ClientInterface $httpClient = null)
    {
        $this->_accessToken = self::requireAccessToken($accessToken);
        $this->_httpClient = $httpClient;
    }

    private static function requireAccessToken(string $accessToken): string
    {
        if ($accessToken === '') {
            throw new ParameterException('アクセストークンは必ず指定してください');
        }

        return $accessToken;
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
     *
     * @throws ParameterException 実効アクセストークンが空文字の場合
     */
    protected function _request(array $options = [], ?string $accessToken = null): Request
    {
        $effectiveAccessToken = $accessToken ?? $this->_accessToken;

        return new Request(new RequestOptions([
            ...$options,
            'authorization' => self::requireAccessToken($effectiveAccessToken),
        ]), $this->_httpClient);
    }

    /**
     * 要求 Entity の直列化結果を JSON の object として送るための値へ変換する。
     *
     * 明示フィールドのない入力 Entity は空配列になり、そのまま JSON 化すると `[]` (配列) になる。
     * API のスキーマは object を要求するため、空のときだけ `{}` になる stdClass へ置き換える。
     *
     * @param array<string, mixed> $fields 要求 Entity の toArrayRecursive() の結果
     * @return array<string, mixed>|\stdClass
     */
    protected static function _jsonObject(array $fields): array|\stdClass
    {
        return $fields === [] ? new \stdClass() : $fields;
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
