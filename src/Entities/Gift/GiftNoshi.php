<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * のし設定。
 */
class GiftNoshi extends Entity
{
    public const OBJECT_FIELDS = [
        'types' => ['array' => true, 'entity' => GiftType::class],
    ];

    protected ?bool $enabled;
    protected ?bool $textEnabled;
    protected ?int $textCharge;
    /** @var list<GiftType> */
    protected array $types;
    protected ?string $comment;

    /**
     * のし設定が有効であるか。
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * のしへの文字入れが有効であるか。
     */
    public function getTextEnabled(): ?bool
    {
        return $this->textEnabled;
    }

    /**
     * のしへの文字入れ料金。
     */
    public function getTextCharge(): ?int
    {
        return $this->textCharge;
    }

    /**
     * のしの種類。
     *
     * @return list<GiftType>
     */
    public function getTypes(): array
    {
        $this->assertFieldInitialized('types');
        return $this->types;
    }

    /**
     * のしに関する注意事項。
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
