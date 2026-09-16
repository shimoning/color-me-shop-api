<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 次に目指す会員ランク
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class NextMembership extends Entity
{
    protected string $membershipId;
    protected string $name;
    protected int $requiredScore;
    protected int $remainingScore;

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
     * 次のランクに到達するために必要な購入金額（税込）
     * @return int
     */
    public function getRequiredScore(): int
    {
        $this->assertFieldInitialized('requiredScore');
        return $this->requiredScore;
    }

    /**
     * 次のランクまでの残り金額（税込）
     * @return int
     */
    public function getRemainingScore(): int
    {
        $this->assertFieldInitialized('remainingScore');
        return $this->remainingScore;
    }
}
