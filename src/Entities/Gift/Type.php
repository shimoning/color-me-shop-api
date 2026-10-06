<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * ギフトの種類。
 */
class Type extends Entity
{
    protected string $name;
    protected int $charge;

    /**
     * 種類名。
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 料金。
     */
    public function getCharge(): int
    {
        $this->assertFieldInitialized('charge');
        return $this->charge;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(Type::class);
