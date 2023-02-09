<?php

namespace Shimoning\ColorMeShopApi;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Collection;

use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Values\Scopes;

use Shimoning\ColorMeShopApi\Services\Sales;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat as SaleStat;

use Shimoning\ColorMeShopApi\Services\Payment;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment as PaymentEntity;

use Shimoning\ColorMeShopApi\Services\Delivery;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;

class Client
{
    protected string $accessToken;

    public function __construct(?string $accessToken = null)
    {
        if ($accessToken) {
            $this->accessToken = $accessToken;
        }
    }

    /**
     * OAuthアプリケーションの登録のための URL を取得する
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86#oauth%E3%82%A2%E3%83%97%E3%83%AA%E3%82%B1%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%B3%E3%81%AE%E7%99%BB%E9%8C%B2
     * @param OAuthOptions $options
     * @param Scopes $scopes
     * @return string
     */
    public function getOAuthUrl(OAuthOptions $options, Scopes $scopes): string
    {
        return (new OAuth($options))->getUrl($scopes);
    }

    /**
     * 認可コードをアクセストークンに交換
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86#%E8%AA%8D%E5%8F%AF%E3%82%B3%E3%83%BC%E3%83%89%E3%82%92%E3%82%A2%E3%82%AF%E3%82%BB%E3%82%B9%E3%83%88%E3%83%BC%E3%82%AF%E3%83%B3%E3%81%AB%E4%BA%A4%E6%8F%9B
     * @param OAuthOptions $options
     * @param string $code
     * @return AccessToken|Errors
     */
    public function exchangeCode2Token(OAuthOptions $options, string $code): AccessToken|Errors
    {
        return (new OAuth($options))->exchangeCode2Token($code);
    }

    /**
     * 受注データのリストを取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSales
     * @param SearchParameters|null $searchParameters
     * @param string|null $accessToken
     * @return Page<Sale>|Errors
     */
    public function getSales(?SearchParameters $searchParameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->salesService($accessToken)->page($searchParameters ?? new SearchParameters([]), $accessToken);
    }

    /**
     * 売上集計の取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param \DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @return SaleStat|Errors
     */
    public function statSales(\DateTimeInterface $dateTime, ?string $accessToken = null): SaleStat|Errors
    {
        return $this->salesService($accessToken)->stat($dateTime, $accessToken);
    }

    /**
     * 受注データの取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
     * @param integer|string $id
     * @param string|null $accessToken
     * @return Sale|Errors
     */
    public function getSale(int|string $id, ?string $accessToken = null): Sale|Errors
    {
        return $this->salesService($accessToken)->one($id, $accessToken);
    }

    /**
     * 受注データの更新
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
     * @param SaleUpdater $updater
     * @param string|null $accessToken
     * @return Sale|Errors
     */
    public function updateSale(SaleUpdater $updater, ?string $accessToken = null): Sale|Errors
    {
        return $this->salesService($accessToken)->update($updater, $accessToken);
    }

    /**
     * 受注のキャンセル
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/cancelSale
     * @param integer|string $id
     * @param boolean|null $restock
     * @param string|null $accessToken
     * @return Sale|Errors
     */
    public function cancelSale(int|string $id, ?bool $restock = false, ?string $accessToken = null): Sale|Errors
    {
        return $this->salesService($accessToken)->cancel($id, $restock, $accessToken);
    }

    /**
     * メールの送信
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/sendSalesMail
     * @param integer|string $id
     * @param MailType $mailType
     * @param string|null $accessToken
     * @return true|Errors
     */
    public function sendSalesMail(int|string $id, MailType $mailType, ?string $accessToken = null): bool|Errors
    {
        return $this->salesService($accessToken)->sendMail($id, $mailType, $accessToken);
    }

    private function salesService(?string $accessToken = null): Sales
    {
        if ($accessToken) {
            $this->accessToken = $accessToken;
        }

        static $service;
        if (!$service) {
            if (empty($this->accessToken)) {
                throw new ParameterException('アクセストークンは必ず指定してください');
            }
            $service = new Sales($this->accessToken);
        }
        return $service;
    }

    /**
     * 決済設定の一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
     * @return Collection<PaymentEntity>|Errors
     */
    public function getPayments(?string $accessToken = null): Collection|Errors
    {
        if ($accessToken) {
            $this->accessToken = $accessToken;
        }
        if (empty($this->accessToken)) {
            throw new ParameterException('アクセストークンは必ず指定してください');
        }

        return (new Payment($this->accessToken))->all();
    }

    /**
     * 配送方法一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
     * @return Collection<DeliveryEntity>|Errors
     */
    public function getDeliveries(?string $accessToken = null): Collection|Errors
    {
        if ($accessToken) {
            $this->accessToken = $accessToken;
        }
        if (empty($this->accessToken)) {
            throw new ParameterException('アクセストークンは必ず指定してください');
        }

        return (new Delivery($this->accessToken))->all();
    }
}
