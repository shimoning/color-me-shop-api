<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\DateTime;

/** 値の単体・配列と、未マークの子への文脈伝播を検証するテストダブル。 */
class FallbackValueEntity extends Entity
{
    public const OBJECT_FIELDS = [
        'value' => ['value' => Furigana::class],
        'mixed' => ['value' => MixedFallbackValue::class],
        'bare' => Furigana::class,
        'values' => ['array' => true, 'value' => Furigana::class],
        'child' => ['entity' => self::class],
        'children' => ['array' => true, 'entity' => self::class],
        'limit' => ['value' => Limit::class],
        'date' => ['value' => DateTime::class],
    ];

    protected ?Furigana $value;
    protected ?MixedFallbackValue $mixed;
    protected ?Furigana $bare;
    /** @var list<Furigana>|null */
    protected ?array $values;
    protected ?self $child;
    /** @var list<self>|null */
    protected ?array $children;
    protected ?Limit $limit;
    protected ?DateTime $date;
}
