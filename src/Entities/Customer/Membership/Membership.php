<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer\Membership;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 顧客が所属する会員ランク
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class Membership extends Entity
{
    const FIELD_TYPES = [
        'progress' => [
            'nullable' => true,
            'entity' => Progress::class,
        ],
    ];

    protected string $membershipId;
    protected string $name;
    protected ?Progress $progress;

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
     * @return Progress|null
     */
    public function getProgress(): ?Progress
    {
        return $this->progress ?? null;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Membership::class);
