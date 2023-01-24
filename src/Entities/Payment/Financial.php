<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\KouzaType;

/**
 * 銀行振り込みの設定情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 */
class Financial extends Entity
{
    const OBJECT_FIELDS = [
        'brands'=> [
            'enum' => KouzaType::class,
        ],
    ];

    protected string $name;
    protected string $branchName;
    protected KouzaType $kouzaTyp;
    protected string $kouzaNumber;
    protected string $kouzaName;

    /**
     * 金融機関名
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 支店名
     * @return string
     */
    public function getBranchName(): string
    {
        return $this->branchName;
    }

    /**
     * 講座種別
     * @return KouzaType
     */
    public function getKouzaType(): KouzaType
    {
        return $this->kouzaType;
    }

    /**
     * 口座番号
     * @return string
     */
    public function getKouzaNumber(): string
    {
        return $this->kouzaNumber;
    }

    /**
     * 口座名義
     * @return string
     */
    public function getKouzaName(): string
    {
        return $this->kouzaName;
    }
}
