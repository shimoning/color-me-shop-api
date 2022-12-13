<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;

/**
 * 顧客
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
 */
class Customer extends Entity
{
    const OBJECT_FIELDS = [
        'sex' => [
            'enum' => true,
            'entity' => Sex::class,
        ],
        'prefId' => [
            'enum' => true,
            'entity' => Prefecture::class,
        ],
    ];

    protected int $id;
    protected string $accountId;
    protected ?string $name;
    protected ?string $furigana;
    protected ?string $hojin;
    protected ?string $busho;
    protected ?Sex $sex;
    protected ?string $birthday;
    protected ?string $postal;
    protected ?Prefecture $prefId;
    protected ?string $prefName;
    protected ?string $address1;
    protected ?string $address2;
    protected ?string $mail;
    protected ?string $tel;
    protected ?string $fax;
    protected ?string $telMobile;
    protected ?string $other;
    protected int $points;
    protected bool $member;
    protected int $salesCount;
    protected ?bool $receiveMailMagazine;
    protected ?string $answerFreeForm1;
    protected ?string $answerFreeForm2;
    protected ?string $answerFreeForm3;

    /**
     * 顧客ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * ショップアカウントID
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * 顧客の名前
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 名前のフリガナ
     * @return string|null
     */
    public function getFurigana(): ?string
    {
        return $this->furigana;
    }

    /**
     * 法人名
     * @return string|null
     */
    public function getHojin(): ?string
    {
        return $this->hojin;
    }

    /**
     * 部署名
     * @return string|null
     */
    public function getBusho(): ?string
    {
        return $this->busho;
    }

    /**
     * 性別
     * @return Sex|null
     */
    public function getSex(): ?Sex
    {
        return $this->sex;
    }

    /**
     * 誕生日
     * @return string|null
     */
    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    /**
     * 郵便番号
     * @return string|null
     */
    public function getPostal(): ?string
    {
        return $this->postal;
    }

    /**
     * 都道府県の通し番号
     * @return Prefecture|null
     */
    public function getPrefId(): ?Prefecture
    {
        return $this->prefId;
    }

    /**
     * 都道府県名
     * @return string|null
     */
    public function getPrefName(): ?string
    {
        return $this->prefName;
    }

    /**
     * 住所1
     * @return string|null
     */
    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    /**
     * 住所2
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    /**
     * メールアドレス
     * @return string|null
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    /**
     * 電話番号
     * @return string|null
     */
    public function getTel(): ?string
    {
        return $this->tel;
    }

    /**
     * FAX番号
     * @return string|null
     */
    public function getFax(): ?string
    {
        return $this->fax;
    }

    /**
     * 携帯電話番号
     * @return string|null
     */
    public function getTelMobile(): ?string
    {
        return $this->telMobile;
    }

    /**
     * 備考
     * @return string|null
     */
    public function getOther(): ?string
    {
        return $this->other;
    }

    /**
     * 保有ポイント数
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    /**
     * 会員登録済みであるか否か
     * @return string|null
     */
    public function isMember(): bool
    {
        return $this->member;
    }

    /**
     * これまでの購入回数
     * @return int
     */
    public function getSalesCount(): int
    {
        return $this->salesCount;
    }

    /**
     * メルマガ受信可否
     * @return bool|null
     */
    public function getReceiveMailMagazine(): ?bool
    {
        return $this->receiveMailMagazine;
    }

    /**
     * フリー項目1の入力内容
     * @return string|null
     */
    public function getAnswerFreeForm1(): ?string
    {
        return $this->answerFreeForm1;
    }

    /**
     * フリー項目2の入力内容
     * @return string|null
     */
    public function getAnswerFreeForm2(): ?string
    {
        return $this->answerFreeForm2;
    }

    /**
     * フリー項目3の入力内容
     * @return string|null
     */
    public function getAnswerFreeForm3(): ?string
    {
        return $this->answerFreeForm3;
    }
}
