<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 配送希望日の設定。
 */
class DeliveryDateDays extends Entity
{
    protected ?bool $enabled;
    protected ?int $default;
    protected int $min;
    protected int $max;
    protected ?string $comment;

    /**
     * 配送希望日選択が有効であるか。
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * デフォルトで選択される、注文日からの日数。
     */
    public function getDefault(): ?int
    {
        return $this->default;
    }

    /**
     * 選択できる最も早い配送日までの日数。
     */
    public function getMin(): int
    {
        $this->assertFieldInitialized('min');
        return $this->min;
    }

    /**
     * 選択できる最も遅い配送日までの日数。
     */
    public function getMax(): int
    {
        $this->assertFieldInitialized('max');
        return $this->max;
    }

    /**
     * 配送希望日に関する注意事項。
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
