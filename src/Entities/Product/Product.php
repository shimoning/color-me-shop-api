<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;

/**
 * 商品 API 読み取り応答: Product。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Product extends Entity
{
    public const OBJECT_FIELDS = [
        'category' => ['entity' => CategoryIds::class],
        'images' => ['array' => true, 'entity' => Image::class],
        'options' => ['array' => true, 'entity' => Option::class],
        'variants' => ['array' => true, 'entity' => Variant::class],
        'pickups' => ['array' => true, 'entity' => Pickup::class],
        'displayState' => ['enum' => ProductDisplayState::class],
    ];

    protected string $accountId;
    protected int $id;
    protected string $name;
    protected ?int $stocks;
    protected bool $stockManaged;
    protected ?int $fewNum;
    protected ?string $modelNumber;
    protected CategoryIds $category;
    /** @var list<int> */
    protected array $groupIds;
    protected ProductDisplayState $displayState;
    protected ?int $salesPrice;
    protected int $salesPriceIncludingTax;
    protected int $salesPriceTax;
    protected ?int $price;
    protected ?int $membersPrice;
    protected int $membersPriceIncludingTax;
    protected int $membersPriceTax;
    protected ?int $cost;
    protected ?int $deliveryCharge;
    protected ?int $coolCharge;
    /** @var list<int> */
    protected array $unavailablePaymentIds;
    /** @var list<int> */
    protected array $unavailableDeliveryIds;
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
    /** @var list<Option> */
    protected array $options;
    /** @var list<Variant> */
    protected array $variants;
    /** @var list<Pickup> */
    protected array $pickups;
    protected bool $regularPurchase;
    protected bool $taxReduced;
    protected bool $withoutShipping;
    protected bool $digitalContent;
    protected bool $unlisted;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (isset($data['group_ids']) && is_array($data['group_ids'])) {
            foreach ($data['group_ids'] as $value) {
                if (! is_int($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, 'group_ids', 'int', new \TypeError('配列要素の型が不正です。'));
                }
            }
        }
        if (isset($data['unavailable_payment_ids']) && is_array($data['unavailable_payment_ids'])) {
            foreach ($data['unavailable_payment_ids'] as $value) {
                if (! is_int($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, 'unavailable_payment_ids', 'int', new \TypeError('配列要素の型が不正です。'));
                }
            }
        }
        if (isset($data['unavailable_delivery_ids']) && is_array($data['unavailable_delivery_ids'])) {
            foreach ($data['unavailable_delivery_ids'] as $value) {
                if (! is_int($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, 'unavailable_delivery_ids', 'int', new \TypeError('配列要素の型が不正です。'));
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
    public function getId(): int
    {
        $this->assertFieldInitialized('id');
        return $this->id;
    }

    /**
     * 商品名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
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
     * 在庫管理するか否か
     * @return bool
     */
    public function getStockManaged(): bool
    {
        $this->assertFieldInitialized('stockManaged');
        return $this->stockManaged;
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
     * category は object。両 ID が 0 の場合は未設定を表す。
     * OpenAPI は nullable とするが、実測では null がなく非 nullable とした。
     * 両 ID が 0 の組み合わせ自体は実測標本に含まれない。
     * 出典: docs/api-product-structure.md (fa4bfbb)。
     * @return CategoryIds
     */
    public function getCategory(): CategoryIds
    {
        $this->assertFieldInitialized('category');
        return $this->category;
    }

    /**
     * 商品が属するグループのIDの配列
     * @return list<int>
     */
    public function getGroupIds(): array
    {
        $this->assertFieldInitialized('groupIds');
        return $this->groupIds;
    }

    /**
     * 掲載設定   - `showing`: 掲載状態  - `hidden`: 非掲載状態  - `showing_for_members`: 会員にのみ掲載  - `sale_for_members`: 掲載状態だが購入は
     * @return ProductDisplayState
     */
    public function getDisplayState(): ProductDisplayState
    {
        $this->assertFieldInitialized('displayState');
        return $this->displayState;
    }

    /**
     * 販売価格
     * @return ?int
     */
    public function getSalesPrice(): ?int
    {
        return $this->salesPrice;
    }

    /**
     * 消費税込販売価格
     * @return int
     */
    public function getSalesPriceIncludingTax(): int
    {
        $this->assertFieldInitialized('salesPriceIncludingTax');
        return $this->salesPriceIncludingTax;
    }

    /**
     * 消費税額
     * @return int
     */
    public function getSalesPriceTax(): int
    {
        $this->assertFieldInitialized('salesPriceTax');
        return $this->salesPriceTax;
    }

    /**
     * 定価
     * @return ?int
     */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    /**
     * 会員価格
     * @return ?int
     */
    public function getMembersPrice(): ?int
    {
        return $this->membersPrice;
    }

    /**
     * 消費税込会員価格
     * @return int
     */
    public function getMembersPriceIncludingTax(): int
    {
        $this->assertFieldInitialized('membersPriceIncludingTax');
        return $this->membersPriceIncludingTax;
    }

    /**
     * 会員価格の消費税額
     * @return int
     */
    public function getMembersPriceTax(): int
    {
        $this->assertFieldInitialized('membersPriceTax');
        return $this->membersPriceTax;
    }

    /**
     * 原価
     * @return ?int
     */
    public function getCost(): ?int
    {
        return $this->cost;
    }

    /**
     * 個別送料
     * @return ?int
     */
    public function getDeliveryCharge(): ?int
    {
        return $this->deliveryCharge;
    }

    /**
     * クール便の追加料金
     * @return ?int
     */
    public function getCoolCharge(): ?int
    {
        return $this->coolCharge;
    }

    /**
     * 利用不可決済方法の配列
     * @return list<int>
     */
    public function getUnavailablePaymentIds(): array
    {
        $this->assertFieldInitialized('unavailablePaymentIds');
        return $this->unavailablePaymentIds;
    }

    /**
     * 利用不可配送方法の配列
     * @return list<int>
     */
    public function getUnavailableDeliveryIds(): array
    {
        $this->assertFieldInitialized('unavailableDeliveryIds');
        return $this->unavailableDeliveryIds;
    }

    /**
     * 最小購入数量
     * @return ?int
     */
    public function getMinNum(): ?int
    {
        return $this->minNum;
    }

    /**
     * 最大購入数量
     * @return ?int
     */
    public function getMaxNum(): ?int
    {
        return $this->maxNum;
    }

    /**
     * 掲載開始時刻
     * @return ?DateTimeImmutable
     */
    public function getSaleStartDate(): ?DateTimeImmutable
    {
        return $this->saleStartDate === null ? null : (new DateTimeImmutable())->setTimestamp($this->saleStartDate);
    }

    /**
     * 掲載終了時刻
     * @return ?DateTimeImmutable
     */
    public function getSaleEndDate(): ?DateTimeImmutable
    {
        return $this->saleEndDate === null ? null : (new DateTimeImmutable())->setTimestamp($this->saleEndDate);
    }

    /**
     * 単位
     * @return ?string
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * 重量(グラム単位)
     * @return ?int
     */
    public function getWeight(): ?int
    {
        return $this->weight;
    }

    /**
     * 売り切れているときもショップに表示するか  商品ごとの設定がない場合は、ショップの在庫表示設定に従った結果を返します。
     * @return bool
     */
    public function getSoldoutDisplay(): bool
    {
        $this->assertFieldInitialized('soldoutDisplay');
        return $this->soldoutDisplay;
    }

    /**
     * 表示順
     * @return ?int
     */
    public function getSort(): ?int
    {
        return $this->sort;
    }

    /**
     * 簡易説明
     * @return ?string
     */
    public function getSimpleExpl(): ?string
    {
        return $this->simpleExpl;
    }

    /**
     * 商品説明
     * @return ?string
     */
    public function getExpl(): ?string
    {
        return $this->expl;
    }

    /**
     * フィーチャーフォン向けショップの商品説明
     * @return ?string
     */
    public function getMobileExpl(): ?string
    {
        return $this->mobileExpl;
    }

    /**
     * スマホ向けショップの商品説明
     * @return ?string
     */
    public function getSmartphoneExpl(): ?string
    {
        return $this->smartphoneExpl;
    }

    /**
     * 商品作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    /**
     * 商品更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }

    /**
     * 備考
     * @return ?string
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * メインの商品画像URL
     * @return ?string
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * メインの商品画像のモバイル用URL
     * @return ?string
     */
    public function getMobileImageUrl(): ?string
    {
        return $this->mobileImageUrl;
    }

    /**
     * メインの商品画像のサムネイルURL
     * @return ?string
     */
    public function getThumbnailImageUrl(): ?string
    {
        return $this->thumbnailImageUrl;
    }

    /**
     * 商品本体の追加画像。画像専用 GET の ProductImage と構造・件数が異なる (docs/api-product-structure.md, fa4bfbb)。
     * @return list<Image>
     */
    public function getImages(): array
    {
        $this->assertFieldInitialized('images');
        return $this->images;
    }

    /**
     * 選択できるオプションの一覧
     * @return list<Option>
     */
    public function getOptions(): array
    {
        $this->assertFieldInitialized('options');
        return $this->options;
    }

    /**
     * 商品バリエーション一覧
     * @return list<Variant>
     */
    public function getVariants(): array
    {
        $this->assertFieldInitialized('variants');
        return $this->variants;
    }

    /**
     * おすすめ商品情報  ※おすすめ商品種別が「3: 新着商品」の情報は、「[新着商品管理](https://help.shop-pro.jp/hc/ja/articles/360062008694)」が「手動モード」の場合の
     * @return list<Pickup>
     */
    public function getPickups(): array
    {
        $this->assertFieldInitialized('pickups');
        return $this->pickups;
    }

    /**
     * 定期購入商品かどうか
     * @return bool
     */
    public function getRegularPurchase(): bool
    {
        $this->assertFieldInitialized('regularPurchase');
        return $this->regularPurchase;
    }

    /**
     * 軽減税率対象なら `true`
     * @return bool
     */
    public function getTaxReduced(): bool
    {
        $this->assertFieldInitialized('taxReduced');
        return $this->taxReduced;
    }

    /**
     * 配送不要商品なら `true`
     * @return bool
     */
    public function getWithoutShipping(): bool
    {
        $this->assertFieldInitialized('withoutShipping');
        return $this->withoutShipping;
    }

    /**
     * OpenAPI の digital_conent は誤記。実応答は digital_content: boolean (docs/api-product-structure.md, fa4bfbb)。
     * @return bool
     */
    public function getDigitalContent(): bool
    {
        $this->assertFieldInitialized('digitalContent');
        return $this->digitalContent;
    }

    /**
     * OpenAPI 未記載。実応答の unlisted: boolean (docs/api-product-structure.md, fa4bfbb)。
     * @return bool
     */
    public function getUnlisted(): bool
    {
        $this->assertFieldInitialized('unlisted');
        return $this->unlisted;
    }
}
