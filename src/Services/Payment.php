<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment as PaymentEntity;

/**
 * 決済設定 API を操作するサービス。
 */
class Payment extends Service
{
    /**
     * 決済設定の一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
     * @param string|null $accessToken
     * @return Collection<PaymentEntity>|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/payments'),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Collection => Collection::cast(PaymentEntity::class, $data['payments'] ?? []),
        );
    }
}
