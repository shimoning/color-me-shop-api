<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * ラッピング設定。
 */
class GiftWrapping extends Entity
{
    public const FIELD_TYPES = [
        'types' => ['array' => true, 'entity' => GiftType::class],
    ];

    protected ?bool $enabled;
    /** @var list<GiftType> */
    protected array $types;
    protected ?string $comment;

    /**
     * ラッピング設定が有効であるか。
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * ラッピングの種類。
     *
     * @return list<GiftType>
     */
    public function getTypes(): array
    {
        $this->assertFieldInitialized('types');
        return $this->types;
    }

    /**
     * ラッピングに関する注意事項。
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
