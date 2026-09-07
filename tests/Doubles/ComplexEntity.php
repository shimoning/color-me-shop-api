<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Constants\MailState;

/**
 * OBJECT_FIELDS の各分岐を網羅するためのテストダブル
 */
class ComplexEntity extends Entity
{
    const OBJECT_FIELDS = [
        'child' => ['entity' => NestedEntity::class],
        'children' => ['array' => true, 'entity' => NestedEntity::class],
        'nullableChild' => ['nullable' => true, 'entity' => NestedEntity::class],
        'nullableChildren' => ['nullable' => true, 'array' => true, 'entity' => NestedEntity::class],
        'limit' => ['value' => Limit::class],
        'limits' => ['array' => true, 'value' => Limit::class],
        'state' => ['enum' => MailState::class],
        'states' => ['array' => true, 'enum' => MailState::class],
        'bare' => NestedEntity::class,
    ];

    protected ?NestedEntity $child;
    protected ?array $children;
    protected ?NestedEntity $nullableChild;
    protected ?array $nullableChildren;
    protected ?Limit $limit;
    protected ?array $limits;
    protected ?MailState $state;
    protected ?array $states;
    protected ?NestedEntity $bare;

    public function getChild(): ?NestedEntity
    {
        return $this->child;
    }

    public function getChildren(): ?array
    {
        return $this->children;
    }

    public function getNullableChild(): ?NestedEntity
    {
        return $this->nullableChild;
    }

    public function getNullableChildren(): ?array
    {
        return $this->nullableChildren;
    }

    public function getLimit(): ?Limit
    {
        return $this->limit;
    }

    public function getLimits(): ?array
    {
        return $this->limits;
    }

    public function getState(): ?MailState
    {
        return $this->state;
    }

    public function getStates(): ?array
    {
        return $this->states;
    }

    public function getBare(): ?NestedEntity
    {
        return $this->bare;
    }
}
