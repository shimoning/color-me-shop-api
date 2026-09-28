<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * メッセージカード設定。
 */
class GiftCard extends Entity
{
    public const OBJECT_FIELDS = [
        'types' => ['array' => true, 'entity' => GiftType::class],
    ];

    protected ?bool $enabled;
    protected ?bool $textEnabled;
    /** @var list<GiftType> */
    protected array $types;
    protected ?string $comment;

    /**
     * メッセージカード設定が有効であるか。
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * メッセージカードへの文字入れが有効であるか。
     */
    public function getTextEnabled(): ?bool
    {
        return $this->textEnabled;
    }

    /**
     * メッセージカードの種類。
     *
     * @return list<GiftType>
     */
    public function getTypes(): array
    {
        $this->assertFieldInitialized('types');
        return $this->types;
    }

    /**
     * メッセージカードに関する注意事項。
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
