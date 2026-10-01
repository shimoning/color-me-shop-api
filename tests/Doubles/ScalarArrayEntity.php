<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

class ScalarArrayEntity extends Entity
{
    public const FIELD_TYPES = [
        'ints' => ['array' => true, 'scalar' => 'int'],
        'nullableStrings' => ['array' => true, 'scalar' => 'string', 'nullable' => true],
        'allowNullInts' => ['array' => true, 'scalar' => 'int', 'allowNull' => true],
    ];

    /** @var list<int> */
    protected array $ints;
    /** @var list<string>|null */
    protected ?array $nullableStrings;
    /** @var list<int>|null */
    protected ?array $allowNullInts;
}
