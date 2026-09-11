<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Constants\MailType;

/**
 * 受注 API を操作するサービス。
 */
class Sales extends Service
{
    /**
     * 受注データのリストを取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSales
     * @param SearchParameters $searchParameters
     * @param string|null $accessToken
     * @return Page<Sale>|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/sales'),
            $searchParameters->toArrayRecursive(),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Page => Page::build(Sale::class, $data, 'sales'),
        );
    }

    /**
     * 受注データの取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
     * @param int|string $id
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function one(int|string $id, ?string $accessToken = null): Sale|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/sales/' . $id),
        );

        return $this->_handle($response, fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * 売上集計の取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param \DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @return Stat|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function stat(
        \DateTimeInterface $dateTime,
        ?string $accessToken = null,
    ): Stat|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/sales/stat'),
            [
                'make_date' => $dateTime->format('Y-m-d'),
            ],
        );

        return $this->_handle($response, fn(?array $data): Stat => new Stat($data['sales_stat'] ?? []));
    }

    /**
     * 受注データの更新
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
     * @param SaleUpdater $updater
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(
        SaleUpdater $updater,
        ?string $accessToken = null,
    ): Sale|Errors {
        $response = $this->_request([
            'json' => true,
        ], $accessToken)->put(
            $this->_endpoint('/sales/' . $updater->getId()),
            [
                'sale' => $updater->toArrayRecursive(),
            ],
        );

        return $this->_handle($response, fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * 受注のキャンセル
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/cancelSale
     * @param int|string $id
     * @param bool|null $restock
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function cancel(
        int|string $id,
        ?bool $restock = false,
        ?string $accessToken = null,
    ): Sale|Errors {
        $response = $this->_request([
            'json' => true,
        ], $accessToken)->put(
            $this->_endpoint('/sales/' . $id . '/cancel'),
            [
                'restock' => $restock,
            ],
        );

        return $this->_handle($response, fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * メールの送信
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/sendSalesMail
     * @param int|string $id
     * @param MailType $mailType
     * @param string|null $accessToken
     * @return true|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function sendMail(
        int|string $id,
        MailType $mailType,
        ?string $accessToken = null,
    ): bool|Errors {
        $response = $this->_request([
            'json' => true,
        ], $accessToken)->post(
            $this->_endpoint('/sales/' . $id . '/mails'),
            [
                'mail' => [
                    'type' => $mailType->value,
                ],
            ],
        );

        return $this->_handle($response, fn(?array $_data): bool => true);
    }
}
