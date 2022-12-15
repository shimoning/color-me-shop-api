<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Values\Scopes;

class OAuth
{
    private OAuthOptions $_options;

    public function __construct(OAuthOptions $options)
    {
        $this->_options = $options;
    }

    /**
     * 認可のための URL を取得する
     *
     * @param Scopes $scopes
     * @return string
     */
    public function getUrl(Scopes $scopes): string
    {
        return $this->_options->getEndpointUri() . '/authorize?' . \http_build_query([
            'client_id' => $this->_options->getClientId(),
            'redirect_uri' => $this->_options->getRedirectUri(),
            'response_type' => 'code', // static
            'scope' => $scopes->get(),
        ], '', null, \PHP_QUERY_RFC3986);
    }

    /**
     * 認可コードをアクセストークンに交換する
     *
     * @param string $code
     * @return AccessToken|Errors
     */
    public function exchangeCode2Token(string $code): AccessToken|Errors
    {
        $response = (new Request(new RequestOptions(['form' => true])))->post(
            $this->_options->getEndpointUri() . '/token',
            [
                'client_id' => $this->_options->getClientId(),
                'client_secret' => $this->_options->getClientSecret(),
                'redirect_uri' => $this->_options->getRedirectUri(),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        return new AccessToken($response->getParsedBody());
    }
}
