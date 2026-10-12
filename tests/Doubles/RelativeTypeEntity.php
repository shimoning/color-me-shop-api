<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

class RelativeTypeEntity extends RelativeTypeParentEntity
{
    protected self $sameType;
    protected parent $parentType;

    public function getSameType(): self
    {
        return $this->sameType;
    }

    public function getParentType(): parent
    {
        return $this->parentType;
    }
}
