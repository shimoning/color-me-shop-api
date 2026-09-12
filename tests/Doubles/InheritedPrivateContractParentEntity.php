<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 継承元の private フィールドに対する横断契約の検証用テストダブル。
 */
class InheritedPrivateContractParentEntity extends Entity
{
    private string $requiredValue;
    private ?string $optionalValue;

    public function getRequiredValue(): string
    {
        $this->assertFieldInitialized('requiredValue');

        return $this->requiredValue;
    }

    public function getOptionalValue(): ?string
    {
        return $this->optionalValue;
    }
}
