<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 独立した productOptionValue スキーマの応答。
 * 商品内 options[].values は文字列配列で、この object ではない。
 * 出典: docs/api-product-structure.md (fa4bfbb)。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class OptionValue extends Entity
{
    protected int $optionId;
    protected int $valueId;
    protected int $productId;
    protected string $accountId;
    protected string $name;
    protected int $makeDate;
    protected int $updateDate;

    /**
     * 商品オプションID
     * @return int
     */
    public function getOptionId(): int
    {
        $this->assertFieldInitialized('optionId');
        return $this->optionId;
    }

    /**
     * 商品オプション値ID
     * @return int
     */
    public function getValueId(): int
    {
        $this->assertFieldInitialized('valueId');
        return $this->valueId;
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
     * 商品オプション値名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 商品オプション値作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable())->setTimestamp($this->makeDate);
    }

    /**
     * 商品オプション値更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable())->setTimestamp($this->updateDate);
    }
}
