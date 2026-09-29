<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\Scopes;

/**
 * OAuth 認証 API を操作するサービス。
 */
class OAuth
{
    private Options $_options;
    private ?ClientInterface $_httpClient;

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86
     * @param Options $options
     * @param ClientInterface|null $httpClient HTTP クライアント (省略時は Guzzle のデフォルト)
     * @return void
     */
    public function __construct(Options $options, ?ClientInterface $httpClient = null)
    {
        $this->_options = $options;
        $this->_httpClient = $httpClient;
    }

    /**
     * 認可のための URL を取得する
     *
     * state は公式ドキュメントに記載されていないが、2026-09-29 に実 API で認可フローを
     * 4 回実行した結果、受理されてコールバックにそのまま返されることを確認している。
     * 認可を拒否した場合のエラー応答 (error=access_denied) にも state が付与される。
     * カラーミー側は値を一度デコードして再エンコードするため、クエリ文字列のバイト列は
     * 送信時と一致しない。実測では %20 が + に、~ が %7E になった。照合はデコード後の値
     * (PHP では $_GET['state']) で行う必要があり、その値は送信した文字列と完全に一致する。
     * 値の生成と保存はライブラリの責務ではないため、利用者がセッション等に保存して
     * コールバックで照合すること。詳細は docs/api-oauth-state-observation.md を参照。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86
     * @param Scopes $scopes
     * @param string|null $state CSRF 対策に使用する state (省略時はクエリに含めない)
     * @return string
     * @throws ParameterException state に空文字を指定した場合
     */
    public function getUrl(Scopes $scopes, ?string $state = null): string
    {
        if ($state === '') {
            throw new ParameterException('state に空文字は指定できません。');
        }

        $query = [
            'client_id' => $this->_options->getClientId(),
            'redirect_uri' => $this->_options->getRedirectUri(),
            'response_type' => 'code', // static
            'scope' => $scopes->get(),
        ];
        if ($state !== null) {
            $query['state'] = $state;
        }

        return $this->_options->getEndpointUri() . '/authorize?'
            . \http_build_query($query, '', null, \PHP_QUERY_RFC3986);
    }

    /**
     * 認可コードをアクセストークンに交換する
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86
     * @param string $code
     * @return AccessToken|ErrorResponse|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function exchangeCode2Token(string $code): AccessToken|ErrorResponse|Errors
    {
        $response = (new Request(new RequestOptions(['form' => true]), $this->_httpClient))->post(
            $this->_options->getEndpointUri() . '/token',
            [
                'client_id' => $this->_options->getClientId(),
                'client_secret' => $this->_options->getClientSecret(),
                'redirect_uri' => $this->_options->getRedirectUri(),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]
        );
        $parsedBody = $response->getParsedBody();
        if ($parsedBody !== null && \array_key_exists('error', $parsedBody)) {
            try {
                return new ErrorResponse($parsedBody, $response);
            } catch (InvalidFieldException) {
                return Errors::build($response);
            }
        }
        if ($parsedBody !== null && \array_key_exists('errors', $parsedBody)) {
            return Errors::build($response);
        }
        if (! $response->isSuccess() || $parsedBody === null) {
            return Errors::build($response);
        }

        return new AccessToken($parsedBody);
    }
}
