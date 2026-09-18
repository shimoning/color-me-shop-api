<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品 API 読み取り応答: Option。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Option extends Entity
{
    protected int $id;
    protected int $productId;
    protected string $accountId;
    protected string $name;
    /** @var list<string> */
    protected array $values;
    protected int $makeDate;
    protected int $updateDate;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (isset($data['values']) && is_array($data['values'])) {
            foreach ($data['values'] as $value) {
                if (! is_string($value)) {
                    throw InvalidFieldException::forArrayElement(self::class, 'values', 'string', new \TypeError('配列要素の型が不正です。'));
                }
            }
        }
        parent::__construct($data);
    }

    /**
     * オプションID
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
     * オプション名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 実応答は文字列配列。独立した productOptionValue object とは異なる (docs/api-product-structure.md, fa4bfbb)。
     * @return list<string>
     */
    public function getValues(): array
    {
        $this->assertFieldInitialized('values');
        return $this->values;
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
