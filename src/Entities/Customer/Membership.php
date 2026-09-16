<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 顧客が所属する会員ランク
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class Membership extends Entity
{
    protected string $membershipId;
    protected string $name;

    /** @var array<string, mixed>|null */
    protected ?array $progress;

    /**
     * 会員ランクID
     * @return string
     */
    public function getMembershipId(): string
    {
        $this->assertFieldInitialized('membershipId');
        return $this->membershipId;
    }

    /**
     * 会員ランク名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 次の会員ランクへの進捗情報
     *
     * @return array<string, mixed>|null
     */
    public function getProgress(): ?array
    {
        return $this->progress;
    }
}
