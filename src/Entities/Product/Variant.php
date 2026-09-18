<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品 API 読み取り応答: Variant。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Variant extends Entity
{
    public const OBJECT_FIELDS = [
        'option1' => ["allowNull" => true, 'entity' => VariantOption::class],
        'option2' => ["allowNull" => true, 'entity' => VariantOption::class],
    ];

    protected int $id;
    protected int $productId;
    protected string $accountId;
    protected ?string $option1Value;
    protected ?string $option2Value;
    protected ?VariantOption $option1;
    protected ?VariantOption $option2;
    protected string $title;
    protected ?int $stocks;
    protected ?int $fewNum;
    protected ?string $modelNumber;
    protected ?int $weight;
    protected ?int $optionMarketPrice;
    protected ?int $optionCost;
    protected ?int $optionPrice;
    protected int $optionPriceIncludingTax;
    protected int $optionPriceTax;
    protected ?int $optionMembersPrice;
    protected int $optionMembersPriceIncludingTax;
    protected int $optionMembersPriceTax;
    protected int $makeDate;
    protected int $updateDate;

    /**
     * 商品バリエーションID
     * @return int
     */
    public function getId(): int
    {
        $this->assertFieldInitialized('id');
        return $this->id;
    }

    /**
     * 商品ID
     * @return int
     */
    public function getProductId(): int
    {
        $this->assertFieldInitialized('productId');
        return $this->productId;
    }

    /**
     * ショップアカウントID
     * @return string
     */
    public function getAccountId(): string
    {
        $this->assertFieldInitialized('accountId');
        return $this->accountId;
    }

    /**
     * オプション1の値
     * @return ?string
     */
    public function getOption1Value(): ?string
    {
        return $this->option1Value;
    }

    /**
     * オプション2の値
     * @return ?string
     */
    public function getOption2Value(): ?string
    {
        return $this->option2Value;
    }

    /**
     * オプション未設定の場合は null
     * @return ?VariantOption
     */
    public function getOption1(): ?VariantOption
    {
        return $this->option1;
    }

    /**
     * 1軸の商品では null (docs/api-product-structure.md, fa4bfbb)。
     * @return ?VariantOption
     */
    public function getOption2(): ?VariantOption
    {
        return $this->option2;
    }

    /**
     * オプション1とオプション2の名前を"　x　"で結合した表示名。オプションが1つしか設定されていない場合はそのオプションの名前に等しい
     * @return string
     */
    public function getTitle(): string
    {
        $this->assertFieldInitialized('title');
        return $this->title;
    }

    /**
     * 在庫数
     * @return ?int
     */
    public function getStocks(): ?int
    {
        return $this->stocks;
    }

    /**
     * 残りわずかとなる在庫数
     * @return ?int
     */
    public function getFewNum(): ?int
    {
        return $this->fewNum;
    }

    /**
     * 型番
     * @return ?string
     */
    public function getModelNumber(): ?string
    {
        return $this->modelNumber;
    }

    /**
     * オプションの重量。設定がない場合は null
     * @return ?int
     */
    public function getWeight(): ?int
    {
        return $this->weight;
    }

    /**
     * オプションの定価。設定がない場合は null
     * @return ?int
     */
    public function getOptionMarketPrice(): ?int
    {
        return $this->optionMarketPrice;
    }

    /**
     * オプションの原価。設定がない場合は null
     * @return ?int
     */
    public function getOptionCost(): ?int
    {
        return $this->optionCost;
    }

    /**
     * 販売価格
     * @return ?int
     */
    public function getOptionPrice(): ?int
    {
        return $this->optionPrice;
    }

    /**
     * 消費税込販売価格
     * @return int
     */
    public function getOptionPriceIncludingTax(): int
    {
        $this->assertFieldInitialized('optionPriceIncludingTax');
        return $this->optionPriceIncludingTax;
    }

    /**
     * 消費税額
     * @return int
     */
    public function getOptionPriceTax(): int
    {
        $this->assertFieldInitialized('optionPriceTax');
        return $this->optionPriceTax;
    }

    /**
     * 会員価格
     * @return ?int
     */
    public function getOptionMembersPrice(): ?int
    {
        return $this->optionMembersPrice;
    }

    /**
     * 消費税込会員価格
     * @return int
     */
    public function getOptionMembersPriceIncludingTax(): int
    {
        $this->assertFieldInitialized('optionMembersPriceIncludingTax');
        return $this->optionMembersPriceIncludingTax;
    }

    /**
     * 会員価格の消費税額
     * @return int
     */
    public function getOptionMembersPriceTax(): int
    {
        $this->assertFieldInitialized('optionMembersPriceTax');
        return $this->optionMembersPriceTax;
    }

    /**
     * オプション作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    /**
     * オプション更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }
}
