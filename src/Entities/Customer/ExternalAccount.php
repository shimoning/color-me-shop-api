<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Constants\ExternalAccountProvider;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 顧客に関連付けられた外部システムの情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class ExternalAccount extends Entity
{
    const OBJECT_FIELDS = [
        'provider' => [
            'enum' => ExternalAccountProvider::class,
        ],
    ];

    protected ExternalAccountProvider $provider;
    protected string $uid;
    protected int $createdAt;

    /**
     * 外部システムの種別
     * @return ExternalAccountProvider
     */
    public function getProvider(): ExternalAccountProvider
    {
        $this->assertFieldInitialized('provider');
        return $this->provider;
    }

    /**
     * 外部システムのユーザーID
     * @return string
     */
    public function getUid(): string
    {
        $this->assertFieldInitialized('uid');
        return $this->uid;
    }

    /**
     * 外部システムとの連携日時
     * @return int
     */
    public function getCreatedAt(): int
    {
        $this->assertFieldInitialized('createdAt');
        return $this->createdAt;
    }
}
