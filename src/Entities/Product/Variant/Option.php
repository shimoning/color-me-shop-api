<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Variant;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * バリエーションの option1 / option2 に含まれる選択値。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class Option extends Entity
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(Option::class);
