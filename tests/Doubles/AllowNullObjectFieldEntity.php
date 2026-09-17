<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * OBJECT_FIELDS の allowNull を検証するテストダブル
 */
class AllowNullObjectFieldEntity extends Entity
{
    const OBJECT_FIELDS = [
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
