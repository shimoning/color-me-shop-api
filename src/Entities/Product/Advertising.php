<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Constants\AdvertisingCondition;
use Shimoning\ColorMeShopApi\Constants\AdvertisingGender;

/**
 * GET /product_advertisings の商品広告要素。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Advertising extends Entity
{
    public const OBJECT_FIELDS = [
        'condition' => ['enum' => AdvertisingCondition::class],
        'gender' => ['enum' => AdvertisingGender::class],
    ];

    protected string $accountId;
    protected int $productId;
    protected ?string $brand;
    /** @var list<string> */
    protected array $colors;
    protected ?AdvertisingCondition $condition;
    protected ?string $description;
    protected ?AdvertisingGender $gender;
    protected ?string $googleProductCategory;
    protected ?string $gtin;
    protected ?string $mpn;
    /** @var list<string> */
    protected array $sizes;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (isset($data['colors']) && is_array($data['colors'])) {
            foreach ($data['colors'] as $value) {
                if (! is_string($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, 'colors', 'string', new \TypeError('配列要素の型が不正です。'));
                }
            }
        }
        if (isset($data['sizes']) && is_array($data['sizes'])) {
            foreach ($data['sizes'] as $value) {
                if (! is_string($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, 'sizes', 'string', new \TypeError('配列要素の型が不正です。'));
                }
            }
        }
        parent::__construct($data);
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
     * @return ?AdvertisingCondition
     */
    public function getCondition(): ?AdvertisingCondition
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
     * @return ?AdvertisingGender
     */
    public function getGender(): ?AdvertisingGender
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
