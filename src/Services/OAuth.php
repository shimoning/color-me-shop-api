<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\Client as GuzzleClient;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Entities\Response;
use Shimoning\ColorMeShopApi\Values\Scopes;

class OAuth
{
    private Options $_options;

    /**
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->_options = $options;
    }

    /**
     * 認可のための URL を取得する
     *
     * @param Scopes $scopes
     * @return string
     */
    public function getOAuthUrl(Scopes $scopes): string
    {
        return $this->_options->getEndpointUri() . '/authorize?' . \http_build_query([
            'client_id' => $this->_options->getClientId(),
            'redirect_uri' => $this->_options->getRedirectUri(),
            'response_type' => 'code', // static
            'scope' => $scopes->getScopesStringified(),
        ], '', null, \PHP_QUERY_RFC3986);
    }

    /**
     * 認可コードをアクセストークンに交換する
     *
     * @param string $code
     * @return AccessToken|bool
     */
    public function exchangeCode2Token(string $code): AccessToken|bool
    {
        $result = (new GuzzleClient)->request(
            'POST',
            $this->_options->getEndpointUri() . '/token',
            [
                'http_errors' => false,
                'timeout' => 0,
                'connect_timeout' => 0,
                'headers' => [
                    'User-Agent' => 'Shimoning ColorMeShopApi Client',
                ],
                'form_params' => [
                    'client_id' => $this->_options->getClientId(),
                    'client_secret' => $this->_options->getClientSecret(),
                    'redirect_uri' => $this->_options->getRedirectUri(),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                ],
            ]
        );
        $response = new Response($result);
        if (! $response->isSuccess()) {
            // TODO: return error instance
            return false;
        }
        return new AccessToken($response->getParsedBody());
    }
}
