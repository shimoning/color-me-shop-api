<?php

namespace Shimoning\ColorMeShopApi\Entities\Shop;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\ShopState;
use Shimoning\ColorMeShopApi\Constants\DomainPlan;
use Shimoning\ColorMeShopApi\Constants\ContractPlan;
use Shimoning\ColorMeShopApi\Constants\OpenState;
use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\TaxType;
use Shimoning\ColorMeShopApi\Constants\TaxRoundingMethod;

/**
 * 商品グループ
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
 */
class Shop extends Entity
{
    const OBJECT_FIELDS = [
        'state' => [
            'enum' => ShopState::class,
        ],
        'domainPlan' => [
            'enum' => DomainPlan::class,
        ],
        'contractPlan' => [
            'enum' => ContractPlan::class,
        ],
        'openState' => [
            'enum' => OpenState::class,
        ],
        'mobileOpenState' => [
            'enum' => OpenState::class,
        ],
        'prefId' => [
            'enum' => Prefecture::class,
        ],
        'taxType' => [
            'enum' => TaxType::class,
        ],
        'taxRoundingMethod' => [
            'enum' => TaxRoundingMethod::class,
        ],
    ];

    protected string $id;
    protected ShopState $state;
    protected DomainPlan $domainPlan;
    protected ContractPlan $contractPlan;

    protected ?int $contractStartDate;
    protected ?int $contractEndDate;
    protected ?int $contractTerm;
    protected int $lastLoginDate;
    protected int $setupDate;
    protected int $makeDate;

    protected string $url;

    protected OpenState $openState;
    protected OpenState $mobileOpenState;

    protected string $loginId;

    protected string $name1;
    protected string $name2;
    protected string $name1Kana;
    protected string $name2Kana;

    protected string $hojin;
    protected string $hojinKana;

    protected string $userMail;
    protected string $tel;
    protected ?string $fax;

    protected string $postal;
    protected ?Prefecture $prefId;
    protected ?string $prefName;
    protected string $address1;
    protected ?string $address2;

    protected string $title;
    protected ?string $titleShort;

    protected string $shopMail1;
    protected ?string $shopMail2;

    protected TaxType $taxType;
    protected int $tax;
    protected TaxRoundingMethod $taxRoundingMethod;
    protected int $reduceTaxRate;

    protected ?string $shopLogoUrl;

    /**
     * ショップアカウントID
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * アカウント状態
     * @return ShopState
     */
    public function getState(): ShopState
    {
        return $this->state;
    }

    /**
     * ドメインプラン
     * @return DomainPlan
     */
    public function getDomainPlan(): DomainPlan
    {
        return $this->domainPlan;
    }

    /**
     * 契約プラン
     * @return ContractPlan
     */
    public function getContractPlan(): ContractPlan
    {
        return $this->contractPlan;
    }

    /**
     * 契約開始日時
     * @return DateTimeImmutable|null
     */
    public function getContractStartDate(): ?DateTimeImmutable
    {
        return $this->contractStartDate
            ? (new DateTimeImmutable)->setTimestamp($this->contractStartDate)
            : null;
    }

    /**
     * 契約終了日時
     * @return DateTimeImmutable|null
     */
    public function getContractEndDate(): ?DateTimeImmutable
    {
        return $this->contractEndDate
            ? (new DateTimeImmutable)->setTimestamp($this->contractEndDate)
            : null;
    }

    /**
     * 契約期間
     * @return int|null
     */
    public function getContractTerm(): ?int
    {
        return $this->contractTerm;
    }

    /**
     * 最終ログイン日時
     * @return DateTimeImmutable
     */
    public function getLastLoginDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->lastLoginDate);
    }

    /**
     * 申し込み完了日時
     * @return DateTimeImmutable
     */
    public function getSetupDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->setupDate);
    }

    /**
     * アカウント作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * ショップURL
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * 開店状態
     * @return OpenState
     */
    public function getOpenState(): OpenState
    {
        return $this->openState;
    }

    /**
     * モバイルショップ開店状態
     * @return OpenState
     */
    public function getMobileOpenState(): OpenState
    {
        return $this->mobileOpenState;
    }

    /**
     * ログインID
     * @return string
     */
    public function getLoginId(): string
    {
        return $this->loginId;
    }

    /**
     * 登録者氏名（姓）
     * @return string
     */
    public function getName1(): string
    {
        return $this->name1;
    }

    /**
     * 登録者氏名（名）
     * @return string
     */
    public function getName2(): string
    {
        return $this->name2;
    }

    /**
     * 登録者氏名カナ（姓）
     * @return string
     */
    public function getName1Kana(): string
    {
        return $this->name1Kana;
    }

    /**
     * 登録者氏名カナ（名）
     * @return string
     */
    public function getName2Kana(): string
    {
        return $this->name2Kana;
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
     * 法人名カナ
     * @return string|null
     */
    public function getHojinKana(): ?string
    {
        return $this->hojinKana;
    }

    /**
     * 登録者メールアドレス
     * @return string
     */
    public function getUserMail(): string
    {
        return $this->userMail;
    }

    /**
     * 登録者電話番号
     * @return string
     */
    public function getTel(): string
    {
        return $this->tel;
    }

    /**
     * 登録者FAX番号
     * @return string|null
     */
    public function getFax(): ?string
    {
        return $this->fax;
    }

    /**
     * 郵便番号
     * @return string
     */
    public function getPostal(): string
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
     * @return string
     */
    public function getAddress1(): string
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
     * ショップ名
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * メールタイトル用ショップ名
     * @return string|null
     */
    public function getTitleShort(): ?string
    {
        return $this->titleShort;
    }

    /**
     * 管理者メールアドレス
     * @return string
     */
    public function getShopMail1(): string
    {
        return $this->shopMail1;
    }

    /**
     * 管理者携帯メールアドレス
     * @return string|null
     */
    public function getShopMail2(): ?string
    {
        return $this->shopMail2;
    }

    /**
     * 消費税の内税・外税設定
     * @return TaxType
     */
    public function getTaxType(): TaxType
    {
        return $this->taxType;
    }

    /**
     * 消費税率
     * @return int
     */
    public function getTax(): int
    {
        return $this->tax;
    }

    /**
     * 消費税の切り捨て、切り上げ設定
     * @return TaxRoundingMethod
     */
    public function getTaxRoundingMethod(): TaxRoundingMethod
    {
        return $this->taxRoundingMethod;
    }

    /**
     * 軽減税率
     * @return int
     */
    public function getReduceTaxRate(): int
    {
        return $this->reduceTaxRate;
    }

    /**
     * ショップロゴ画像のURL
     * @return string|null
     */
    public function getShopLogoUrl(): ?string
    {
        return $this->shopLogoUrl;
    }
}
