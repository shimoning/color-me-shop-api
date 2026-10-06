<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Gift;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * のし設定。
 */
class Noshi extends Entity
{
    public const FIELD_TYPES = [
        'types' => ['array' => true, 'entity' => Type::class],
    ];

    protected ?bool $enabled;
    protected ?bool $textEnabled;
    protected ?int $textCharge;
    /** @var list<Type> */
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
     * @return list<Type>
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(Noshi::class);
