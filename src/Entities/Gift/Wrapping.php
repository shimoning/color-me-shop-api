<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * ラッピング設定。
 */
class Wrapping extends Entity
{
    public const FIELD_TYPES = [
        'types' => ['array' => true, 'entity' => Type::class],
    ];

    protected ?bool $enabled;
    /** @var list<Type> */
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
     * @return list<Type>
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(Wrapping::class);
