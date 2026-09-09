<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use Shimoning\ColorMeShopApi\Constants\AuthRedirectUri;

/**
 * OAuth 認証に必要なアプリケーション設定。
 */
class Options
{
    const ENDPOINT_URI = 'https://api.shop-pro.jp/oauth';

    private string $endpointUri;
    private string $clientId;
    private string $clientSecret;
    private AuthRedirectUri|string $redirectUri;
    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param \Shimoning\ColorMeShopApi\Constants\AuthRedirectUri|string $redirectUri
     * @param string|null $endpointUri
     * @return void
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        AuthRedirectUri|string $redirectUri,
        ?string $endpointUri = null,
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->endpointUri = $endpointUri ?? self::ENDPOINT_URI;
    }

    /**
     * クライアントIDを取得
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * クライアントシークレットを取得
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * リダイレクトURIを設定し、文字列化した値を返す
     *
     * @param AuthRedirectUri|string $uri
     * @return string
     */
    public function setRedirectUri(AuthRedirectUri|string $uri): string
    {
        $this->redirectUri = $uri;
        return $this->getRedirectUri();
    }

    /**
     * リダイレクトURI
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri instanceof AuthRedirectUri
            ? $this->redirectUri->value
            : $this->redirectUri;
    }

    /**
     * エンドポイントのURIを取得
     * @return string
     */
    public function getEndpointUri(): string
    {
        return $this->endpointUri;
    }
}
