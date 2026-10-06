<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Stock;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryIds;
use Shimoning\ColorMeShopApi\Entities\Product\Image;

/**
 * 在庫情報取得 API の応答 1 行。
 */
class Stock extends Entity
{
    public const FIELD_TYPES = [
        'category' => ['entity' => CategoryIds::class, 'allowNull' => true],
        'images' => ['array' => true, 'entity' => Image::class],
        'displayState' => ['enum' => ProductDisplayState::class],
    ];

    protected string $accountId;
    protected int $productId;
    protected string $name;
    protected ?string $option1Value;
    protected ?string $option2Value;
    protected ?int $stocks;
    protected ?int $fewNum;
    protected ?string $modelNumber;
    protected ?string $variantModelNumber;
    protected ?CategoryIds $category;
    protected ProductDisplayState $displayState;
    protected ?int $salesPrice;
    protected ?int $price;
    protected ?int $membersPrice;
    protected ?int $cost;
    protected ?int $deliveryCharge;
    protected ?int $coolCharge;
    protected ?int $minNum;
    protected ?int $maxNum;
    protected ?int $saleStartDate;
    protected ?int $saleEndDate;
    protected ?string $unit;
    protected ?int $weight;
    protected bool $soldoutDisplay;
    protected ?int $sort;
    protected ?string $simpleExpl;
    protected ?string $expl;
    protected ?string $mobileExpl;
    protected ?string $smartphoneExpl;
    protected int $makeDate;
    protected int $updateDate;
    protected ?string $memo;
    protected ?string $imageUrl;
    protected ?string $mobileImageUrl;
    protected ?string $thumbnailImageUrl;
    /** @var list<Image> */
    protected array $images;

    public function getAccountId(): string
    {
        $this->assertFieldInitialized('accountId');
        return $this->accountId;
    }

    public function getProductId(): int
    {
        $this->assertFieldInitialized('productId');
        return $this->productId;
    }

    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    public function getOption1Value(): ?string
    {
        return $this->option1Value;
    }

    public function getOption2Value(): ?string
    {
        return $this->option2Value;
    }

    public function getStocks(): ?int
    {
        return $this->stocks;
    }

    public function getFewNum(): ?int
    {
        return $this->fewNum;
    }

    public function getModelNumber(): ?string
    {
        return $this->modelNumber;
    }

    public function getVariantModelNumber(): ?string
    {
        return $this->variantModelNumber;
    }

    public function getCategory(): ?CategoryIds
    {
        return $this->category;
    }

    public function getDisplayState(): ProductDisplayState
    {
        $this->assertFieldInitialized('displayState');
        return $this->displayState;
    }

    public function getSalesPrice(): ?int
    {
        return $this->salesPrice;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function getMembersPrice(): ?int
    {
        return $this->membersPrice;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function getDeliveryCharge(): ?int
    {
        return $this->deliveryCharge;
    }

    public function getCoolCharge(): ?int
    {
        return $this->coolCharge;
    }

    public function getMinNum(): ?int
    {
        return $this->minNum;
    }

    public function getMaxNum(): ?int
    {
        return $this->maxNum;
    }

    public function getSaleStartDate(): ?DateTimeImmutable
    {
        return $this->saleStartDate === null
            ? null
            : (new DateTimeImmutable())->setTimestamp($this->saleStartDate);
    }

    public function getSaleEndDate(): ?DateTimeImmutable
    {
        return $this->saleEndDate === null
            ? null
            : (new DateTimeImmutable())->setTimestamp($this->saleEndDate);
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function getSoldoutDisplay(): bool
    {
        $this->assertFieldInitialized('soldoutDisplay');
        return $this->soldoutDisplay;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function getSimpleExpl(): ?string
    {
        return $this->simpleExpl;
    }

    public function getExpl(): ?string
    {
        return $this->expl;
    }

    public function getMobileExpl(): ?string
    {
        return $this->mobileExpl;
    }

    public function getSmartphoneExpl(): ?string
    {
        return $this->smartphoneExpl;
    }

    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getMobileImageUrl(): ?string
    {
        return $this->mobileImageUrl;
    }

    public function getThumbnailImageUrl(): ?string
    {
        return $this->thumbnailImageUrl;
    }

    /** @return list<Image> */
    public function getImages(): array
    {
        $this->assertFieldInitialized('images');
        return $this->images;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Stock::class);
