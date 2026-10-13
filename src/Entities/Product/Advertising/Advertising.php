<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Advertising;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\Product\Advertising\Condition;
use Shimoning\ColorMeShopApi\Constants\Product\Advertising\Gender;

/**
 * GET /product_advertisings の商品広告要素。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
 */
class Advertising extends Entity
{
    public const FIELD_TYPES = [
        'condition' => ['enum' => Condition::class],
        'gender' => ['enum' => Gender::class],
        'colors' => ['array' => true, 'scalar' => 'string'],
        'sizes' => ['array' => true, 'scalar' => 'string'],
    ];

    protected string $accountId;
    protected int $productId;
    protected ?string $brand;
    /** @var list<string> */
    protected array $colors;
    protected ?Condition $condition;
    protected ?string $description;
    protected ?Gender $gender;
    protected ?string $googleProductCategory;
    protected ?string $gtin;
    protected ?string $mpn;
    /** @var list<string> */
    protected array $sizes;

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
     * 商品ID
     * @return int
     */
    public function getProductId(): int
    {
        $this->assertFieldInitialized('productId');
        return $this->productId;
    }

    /**
     * ブランド(メーカー)
     * @return ?string
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * 商品の主な色の一覧
     * @return list<string>
     */
    public function getColors(): array
    {
        $this->assertFieldInitialized('colors');
        return $this->colors;
    }

    /**
     * 商品の状態 - `new`: 新品 - `used`: 中古品
     * @return ?Condition
     */
    public function getCondition(): ?Condition
    {
        return $this->condition;
    }

    /**
     * 広告用商品説明
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * 商品が対象となる性別 - `male`: メンズ - `female`: レディース - `unisex`: ユニセックス
     * @return ?Gender
     */
    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    /**
     * Google 商品カテゴリに準拠した広告用カテゴリー
     * @return ?string
     */
    public function getGoogleProductCategory(): ?string
    {
        return $this->googleProductCategory;
    }

    /**
     * 商品の国際取引商品番号 GTIN (JANコード/ISBN)
     * @return ?string
     */
    public function getGtin(): ?string
    {
        return $this->gtin;
    }

    /**
     * 製品番号 MPN
     * @return ?string
     */
    public function getMpn(): ?string
    {
        return $this->mpn;
    }

    /**
     * 商品サイズの一覧
     * @return list<string>
     */
    public function getSizes(): array
    {
        $this->assertFieldInitialized('sizes');
        return $this->sizes;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Advertising::class);
