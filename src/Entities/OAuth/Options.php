<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use Shimoning\ColorMeShopApi\Constants\AuthRedirectUri;

class Options
{
    const ENDPOINT_URI = 'https://api.shop-pro.jp/oauth';

    private string $endpointUri;
    private string $clientId;
    private string $clientSecret;
    private AuthRedirectUri|string $redirectUri;
    /**
     * @param string|null $endpointUri
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @param \Shimoning\ColorMeShopApi\Constants\AuthRedirectUri|string|null $redirectUri
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        mixed $redirectUri,
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
     * リダイレクトURI
     * @return AuthRedirectUri|string $uri
     */
    public function setRedirectUri(AuthRedirectUri|string $uri): string
    {
        return $this->redirectUri = $uri;
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
