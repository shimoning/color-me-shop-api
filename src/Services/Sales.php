<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\Client as GuzzleClient;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Response;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Entities\Pages;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;

class Sales
{
    protected string $_accessToken;

    public function __construct(string $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    public function getList(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Pages | bool {
        $result = (new GuzzleClient)->request(
            'GET',
            'https://api.shop-pro.jp/v1/sales?' . \http_build_query($searchParameters->toArray()),
            [
                'http_errors' => false,
                'timeout' => 0,
                'connect_timeout' => 0,
                'headers' => [
                    'User-Agent' => 'Shimoning ColorMeShopApi Client',
                    'Authorization' => 'Bearer ' . ($accessToken ?? $this->_accessToken),
                ],
            ]
        );
        $response = new Response($result);
        if (! $response->isSuccess()) {
            // TODO: return error instance
            return false;
        }
        $data = $response->getParsedBody();

        return new Pages(
            Collection::cast(Sale::class, $data['sales'] ?? []),
            new Pagination($data['meta'] ?? []),
        );
    }
}
