<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * バリエーションの option1 / option2 に含まれる選択値。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class VariantOption extends Entity
{
    protected int $id;
    protected string $name;
    protected ?int $valueId;
    protected ?string $value;

    /**
     * オプションのID
     * @return int
     */
    public function getId(): int
    {
        $this->assertFieldInitialized('id');
        return $this->id;
    }

    /**
     * オプションの名前
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * オプションの値ID
     * @return ?int
     */
    public function getValueId(): ?int
    {
        return $this->valueId;
    }

    /**
     * オプションの値の名前
     * @return ?string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
