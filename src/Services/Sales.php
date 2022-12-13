<?php

namespace Shimoning\ColorMeShopApi\Services;

use DateTimeInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\Options as RequestOption;

use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Entities\Page;

class Sales
{
    protected string $_accessToken;

    public function __construct(string $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page | bool {
        $response = (new Request(new RequestOption([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales?' . \http_build_query($searchParameters->toArray()),
        );
        if (! $response->isSuccess()) {
            // TODO: return error instance
            return false;
        }
        $data = $response->getParsedBody();

        return new Page(
            Collection::cast(Sale::class, $data['sales'] ?? []),
            new Pagination($data['meta'] ?? []),
        );
    }

    public function one(string $id, ?string $accessToken = null): Sale | false
    {
        $response = (new Request(new RequestOption([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales/' . $id,
        );
        if (! $response->isSuccess()) {
            // TODO: return error instance
            return false;
        }
        $data = $response->getParsedBody();
        return new Sale($data['sale'] ?? []);
    }

    // TODO: implement
    public function stat(DateTimeInterface $dateTime, ?string $accessToken = null)
    {
        $response = (new Request(new RequestOption([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales/stat?' . \http_build_query([
                'make_date' => $dateTime->format('Y-m-d'),
            ]),
        );
        if (! $response->isSuccess()) {
            // TODO: return error instance
            return false;
        }
        $data = $response->getParsedBody();
        return new Stat($data['sales_stat'] ?? []);
    }

    // TODO: implement
    public function update() {}

    // TODO: implement
    public function cancel() {}

    // TODO: implement
    public function sendMail() {}
}
