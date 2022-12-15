<?php

namespace Shimoning\ColorMeShopApi\Services;

use DateTimeInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Constants\MailType;

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
     * @return Page<Sale>|Errors
     */
    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales',
            $searchParameters->toArrayRecursive(),
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return new Page(
            Collection::cast(Sale::class, $data['sales'] ?? []),
            new Pagination($data['meta'] ?? []),
        );
    }

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
     * @param int|string $id
     * @param string|null $accessToken
     * @return Sale|Errors
     */
    public function one(int|string $id, ?string $accessToken = null): Sale|Errors
    {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales/' . $id,
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();
        return new Sale($data['sale'] ?? []);
    }

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @param string|null $accessToken
     * @return Stat|Errors
     */
    public function stat(
        DateTimeInterface $dateTime,
        ?string $accessToken = null,
    ): Stat|Errors {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/sales/stat?',
            [
                'make_date' => $dateTime->format('Y-m-d'),
            ],
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();
        return new Stat($data['sales_stat'] ?? []);
    }

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
     * @param SaleUpdater $updater
     * @param string|null $accessToken
     * @return Sale|Errors
     */
    public function update(
        SaleUpdater $updater,
        ?string $accessToken = null,
    ): Sale|Errors {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
            'json' => true,
        ])))->put(
            'https://api.shop-pro.jp/v1/sales/' . $updater->getId(),
            [
                'sale' => $updater->toArrayRecursive(),
            ],
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();
        return new Sale($data['sale'] ?? []);
    }

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/cancelSale
     * @param int|string $id
     * @param bool|null $restock
     * @param string|null $accessToken
     * @return Sale|Errors
     */
    public function cancel(
        int|string $id,
        ?bool $restock = false,
        ?string $accessToken = null,
    ): Sale|Errors {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
            'json' => true,
        ])))->put(
            'https://api.shop-pro.jp/v1/sales/' . $id . '/cancel',
            [
                'restock' => $restock,
            ],
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();
        return new Sale($data['sale'] ?? []);
    }

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/sendSalesMail
     * @param int|string $id
     * @param MailType $mailType
     * @param string|null $accessToken
     * @return true|Errors
     */
    public function sendMail(
        int|string $id,
        MailType $mailType,
        ?string $accessToken = null,
    ): bool|Errors {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
            'json' => true,
        ])))->post(
            'https://api.shop-pro.jp/v1/sales/' . $id . '/mails',
            [
                'mail' => [
                    'type' => $mailType->value,
                ],
            ],
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        return true;
    }
}
