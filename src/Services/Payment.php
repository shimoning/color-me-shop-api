<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment as PaymentEntity;

class Payment
{
    protected string $_accessToken;

    public function __construct(string $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
     * @return Collection<PaymentEntity>|Errors
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/payments',
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return Collection::cast(PaymentEntity::class, $data['payments'] ?? []);
    }
}
