<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 初期化済みの promoted readonly フィールドを検証するテストダブル。
 */
class PromotedReadonlyFieldEntity extends Entity
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        protected readonly ?string $name = 'constructor',
    ) {
        parent::__construct($data);
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
