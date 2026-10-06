<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Option;

use Shimoning\ColorMeShopApi\Aliases;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品 API 読み取り応答: Option。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Option extends Entity
{
    public const FIELD_TYPES = [
        'values' => ['array' => true, 'scalar' => 'string'],
    ];

    protected int $id;
    protected int $productId;
    protected string $accountId;
    protected string $name;
    /** @var list<string> */
    protected array $values;
    /**
     * 公式 OpenAPI との差分: nullable ではないが、実 API は `null` を返す (2026-09-21)。
     *
     * @see docs/api-product-structure.md
     */
    protected ?int $makeDate;
    protected int $updateDate;

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
     * オプション値の名前のリスト。独立した `Value` の object ではなく文字列である。
     *
     * @return list<string>
     * @see docs/api-product-structure.md
     */
    public function getValues(): array
    {
        $this->assertFieldInitialized('values');
        return $this->values;
    }

    /**
     * オプション作成日時
     *
     * 公式 OpenAPI との差分: nullable ではないが、実 API は `null` を返す (2026-09-21)。
     *
     * @return DateTimeImmutable|null
     * @see docs/api-product-structure.md
     */
    public function getMakeDate(): ?DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        if ($this->makeDate === null) {
            return null;
        }
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Option::class);
