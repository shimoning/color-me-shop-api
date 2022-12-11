<?php

namespace Shimoning\ColorMeShopApi\Entities\Input\OAuth;

use Shimoning\ColorMeShopApi\Constants\AuthRedirectUri;

class Options
{
    const ENDPOINT_URI = 'https://api.shop-pro.jp/oauth';

    private string $_endpointUri;
    private string $_clientId;
    private string $_clientSecret;
    private AuthRedirectUri|string $_redirectUri;
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
        $this->_clientId = $clientId;
        $this->_clientSecret = $clientSecret;
        $this->_redirectUri = $redirectUri;
        $this->_endpointUri = $endpointUri ?? self::ENDPOINT_URI;
    }

    /**
     * クライアントIDを取得
     * @return string
     */
    public function getClientId(): string
    {
        return $this->_clientId;
    }

    /**
     * クライアントシークレットを取得
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->_clientSecret;
    }

    /**
     * リダイレクトURI
     * @return AuthRedirectUri|string $uri
     */
    public function setRedirectUri(AuthRedirectUri|string $uri): string
    {
        return $this->_redirectUri = $uri;
    }

    /**
     * リダイレクトURI
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->_redirectUri instanceof AuthRedirectUri
            ? $this->_redirectUri->value
            : $this->_redirectUri;
    }

    /**
     * エンドポイントのURIを取得
     * @return string
     */
    public function getEndpointUri(): string
    {
        return $this->_endpointUri;
    }
}
