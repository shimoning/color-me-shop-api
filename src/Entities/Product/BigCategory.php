<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 大カテゴリー。実測レスポンスでは children キーを持つ。
 */
class BigCategory extends Category
{
    /** @var list<SmallCategory> */
    protected array $children;

    /**
     * @param array<string, mixed> $data API レスポンスデータ
     */
    public function __construct(array $data)
    {
        if (\array_key_exists('children', $data) && ! \is_array($data['children'])) {
            throw InvalidFieldException::for(static::class, 'children', 'array', $data['children']);
        }

        parent::__construct($data);

        if (! \array_key_exists('children', $data)) {
            return;
        }

        $this->children = [];
        foreach ($data['children'] as $index => $child) {
            try {
                $category = Category::fromArray($child);
                if (! $category instanceof SmallCategory) {
                    throw new \UnexpectedValueException('子カテゴリーの id_small は 0 以外を期待します。');
                }
                $this->children[] = $category;
            } catch (\Throwable $error) {
                throw InvalidFieldException::forArrayElement(
                    static::class,
                    \sprintf('children[%s]', $index),
                    SmallCategory::class,
                    $error,
                );
            }
        }
    }

    /**
     * 子カテゴリー。実測した大カテゴリーは常に children キーを持つため、欠損時は
     * 非 nullable な既存契約に従い MissingFieldException を送出する。
     * children は入力のキーにかかわらず、0 から始まる連番キーのリストとして返す。
     *
     * @return list<SmallCategory>
     */
    public function getChildren(): array
    {
        $this->assertFieldInitialized('children');
        return $this->children;
    }
}
