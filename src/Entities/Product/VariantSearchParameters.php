<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品バリエーション一覧 GET の検索条件。実測の既定 limit は 10。
 */
class VariantSearchParameters extends Entity implements RequestEntity
{
    protected ?string $modelNumber;
    protected ?string $fields;
    protected ?int $limit;
    protected ?int $offset;
}
