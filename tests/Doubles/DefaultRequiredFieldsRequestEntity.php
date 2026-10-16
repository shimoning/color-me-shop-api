<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;

class DefaultRequiredFieldsRequestEntity extends Entity implements RequestEntity
{
    protected ?string $optionalField;
}
