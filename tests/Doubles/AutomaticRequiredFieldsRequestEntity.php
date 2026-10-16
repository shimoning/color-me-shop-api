<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;

class AutomaticRequiredFieldsRequestEntity extends Entity implements RequestEntity
{
    public const REQUIRED_FIELDS = ['required_field'];

    protected string $requiredField;
}
