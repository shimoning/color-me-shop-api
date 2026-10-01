<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * FIELD_TYPES の allowNull を検証するテストダブル
 */
class AllowNullObjectFieldEntity extends Entity
{
    const FIELD_TYPES = [
        'allowNullChild' => ['allowNull' => true, 'entity' => NestedEntity::class],
        'nullableAllowNullChild' => ['nullable' => true, 'allowNull' => true, 'entity' => NestedEntity::class],
    ];

    protected ?NestedEntity $allowNullChild;
    protected ?NestedEntity $nullableAllowNullChild;

    public function getAllowNullChild(): ?NestedEntity
    {
        return $this->allowNullChild;
    }

    public function getNullableAllowNullChild(): ?NestedEntity
    {
        return $this->nullableAllowNullChild;
    }
}
