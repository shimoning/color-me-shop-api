<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 受注明細
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class SaleDetail extends Entity
{
    protected int $id;
    protected int $saleId;
    protected string $accountId;
    protected int $productId;
    protected ?int $saleDeliveryId;

    protected ?string $option1Value;
    protected ?string $option2Value;
    protected ?int $option1Index;
    protected ?int $option2Index;

    protected ?string $productModelNumber;
    protected string $productName;
    protected string $pristineProductFullName;
    protected ?int $productCost;

    protected ?string $productImageUrl;
    protected ?string $productThumbnailImageUrl;
    protected ?string $productMobileImageUrl;

    protected int $price;
    protected int $priceWithTax;
    protected int $productNum;
    protected ?string $unit;
    protected int $subtotalPrice;

    /**
     * 受注明細ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 売上ID
     * @return int
     */
    public function getSaleId(): int
    {
        return $this->saleId;
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
     * 商品ID
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * お届け先ID
     * @return int|null
     */
    public function getSaleDeliveryId(): ?int
    {
        return $this->saleDeliveryId;
    }

    /**
     * オプション1の値(最新の商品情報)
     * @return string|null
     */
    public function getOption1Value(): ?string
    {
        return $this->option1Value;
    }

    /**
     * オプション2の値(最新の商品情報)
     * @return string|null
     */
    public function getOption2Value(): ?string
    {
        return $this->option2Value;
    }

    /**
     * オプション1の値の選択肢中の位置
     * @return int|null
     */
    public function getOption1Index(): ?int
    {
        return $this->option1Index;
    }

    /**
     * オプション2の値の選択肢中の位置
     * @return int|null
     */
    public function getOption2Index(): ?int
    {
        return $this->option2Index;
    }

    /**
     * 型番
     * @return string|null
     */
    public function getProductModelNumber(): ?string
    {
        return $this->productModelNumber;
    }

    /**
     * 商品名(最新の商品情報)
     * @return string
     */
    public function getProductName(): string
    {
        return $this->productName;
    }

    /**
     * 商品名とオプション名(注文時の商品情報)
     * @return string
     */
    public function getPristineProductFullName(): string
    {
        return $this->pristineProductFullName;
    }

    /**
     * 商品原価
     * @return int|null
     */
    public function getProductCost(): ?int
    {
        return $this->productCost;
    }

    /**
     * 商品画像URL
     * @return string|null
     */
    public function getProductImageUr(): ?string
    {
        return $this->productImageUrl;
    }

    /**
     * サムネイル用商品画像URL
     * @return string|null
     */
    public function getProductThumbnailImageUrl(): ?string
    {
        return $this->productThumbnailImageUrl;
    }

    /**
     * モバイル用商品画像URL
     * @return string|null
     */
    public function getProductMobileImageUrl(): ?string
    {
        return $this->productMobileImageUrl;
    }

    /**
     * 商品販売価格
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * 税込み商品価格
     * @return int
     */
    public function getPriceWithTax(): int
    {
        return $this->priceWithTax;
    }

    /**
     * 商品点数
     * @return int
     */
    public function getProductNum(): int
    {
        return $this->productNum;
    }

    /**
     * 単位
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * 商品小計。販売価格と点数の積
     * @return int
     */
    public function getSubtotalPrice(): int
    {
        return $this->subtotalPrice;
    }
}
