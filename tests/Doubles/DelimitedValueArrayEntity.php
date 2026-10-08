<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Sort;

class DelimitedValueArrayEntity extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'sorts' => [
            'array' => true,
            'value' => Sort::class,
            'delimiter' => ',',
        ],
    ];

    /** @var list<Sort>|null */
    protected ?array $sorts;
}
