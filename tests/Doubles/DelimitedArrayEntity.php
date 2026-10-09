<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

class DelimitedArrayEntity extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'ids' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'fields' => ['array' => true, 'scalar' => 'string', 'delimiter' => '|'],
    ];

    /** @var list<int>|null */
    protected ?array $ids;

    /** @var list<string>|null */
    protected ?array $fields;
}
