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

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSales
     * @param SearchParameters $searchParameters
     * @param string|null $accessToken
     * @return Page<Sale>|bool
     */
    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page|bool {
        $response = (new Request(new RequestOption([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales',
            $searchParameters->toArrayRecursive(),
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

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
     * @param string $id
     * @param string|null $accessToken
     * @return Sale|bool
     */
    public function one(string $id, ?string $accessToken = null): Sale|bool
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

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @return Stat|bool
     */
    public function stat(DateTimeInterface $dateTime, ?string $accessToken = null): Stat|bool
    {
        $response = (new Request(new RequestOption([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales/stat?',
            [
                'make_date' => $dateTime->format('Y-m-d'),
            ],
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

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/cancelSale
     * @param string $id
     * @param boolean|null $restock
     * @return Sale|boolean
     */
    public function cancel(string $id, ?bool $restock = false): Sale|bool
    {
        $response = (new Request(new RequestOption([
            'authorization' => $accessToken ?? $this->_accessToken,
            'json' => true,
        ])))->put(
            'https://api.shop-pro.jp/v1/sales/' . $id . '/cancel',
            [
                'restock' => $restock,
            ],
        );
        if (! $response->isSuccess()) {
            // TODO: return error instance
            return false;
        }
        $data = $response->getParsedBody();
        return new Sale($data['sale'] ?? []);
    }

    // TODO: implement
    public function sendMail() {}
}
