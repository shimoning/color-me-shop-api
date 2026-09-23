<?php

namespace Shimoning\ColorMeShopApi\Entities\Product;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品カテゴリー
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
 */
abstract class Category extends Entity
{
    const OBJECT_FIELDS = [
        'displayState' => [
            'enum' => CategoryDisplayState::class,
        ],
        'metaTag' => [
            'allowNull' => true,
            'entity' => MetaTag::class,
        ],
    ];

    protected int $idBig;
    protected int $idSmall;
    protected string $accountId;

    protected string $name;

    protected ?string $imageUrl;
    protected ?string $expl;

    protected ?int $sort;
    protected CategoryDisplayState $displayState;

    protected int $makeDate;
    protected int $updateDate;

    protected ?MetaTag $metaTag;

    /**
     * id_small の実測上の親子判別に従ってカテゴリーを生成する。
     *
     * @param array<string, mixed> $data API レスポンスデータ
     * @throws InvalidFieldException id_small が欠損または整数以外の場合
     */
    public static function fromArray(array $data): BigCategory|SmallCategory
    {
        $idSmall = $data['id_small'] ?? null;
        if (! \is_int($idSmall)) {
            throw InvalidFieldException::for(self::class, 'id_small', 'int', $idSmall);
        }

        return $idSmall === 0 ? new BigCategory($data) : new SmallCategory($data);
    }

    /**
     * 大カテゴリーID
     * @return int
     */
    public function getIdBig(): int
    {
        $this->assertFieldInitialized('idBig');
        return $this->idBig;
    }
    /**
     * 小カテゴリーID。大カテゴリーのことを表している場合は0
     * @return int
     */
    public function getIdSmall(): int
    {
        $this->assertFieldInitialized('idSmall');
        return $this->idSmall;
    }

    /**
     * ショップアカウントID
     * @return string
     */
    public function getAccountId(): string
    {
        $this->assertFieldInitialized('accountId');
        return $this->accountId;
    }

    /**
     * 商品カテゴリー名
     * @return string
     */
    public function getName(): string
    {
        $this->assertFieldInitialized('name');
        return $this->name;
    }

    /**
     * 商品カテゴリー画像URL
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * 商品カテゴリー説明
     *
     * 実 API の観測 (2026-09-21) では、書き込みで明示的な `null` を送っても旧値のまま残り、
     * 空文字 `""` は保存された。出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
     *
     * @return string|null
     */
    public function getExpl(): ?string
    {
        return $this->expl;
    }

    /**
     * 商品カテゴリーのメタタグ
     *
     * 公式 OpenAPI（2026-09-16 確認）では meta_tag 自体は nullable ではない。
     * 2026-09-12 の実 API 検証では親カテゴリー2件中1件で meta_tag の欠損を確認したため、
     * 欠損または明示的な null の場合は null を返す。2026-09-21 の書き込み観測では、大カテゴリーは
     * 作成直後は meta_tag キー自体が無く、一度設定すると全キーを null に戻してもキーが残った。
     * また部分更新はマージではなく置換で、送らなかったキーは null になる。出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
     *
     * @return MetaTag|null
     */
    public function getMetaTag(): ?MetaTag
    {
        return $this->metaTag ?? null;
    }

    /**
     * 表示順
     * @return int|null
     */
    public function getSort(): ?int
    {
        return $this->sort;
    }

    /**
     * 表示状態
     * @return CategoryDisplayState
     */
    public function getDisplayState(): CategoryDisplayState
    {
        $this->assertFieldInitialized('displayState');
        return $this->displayState;
    }

    /**
     * 商品カテゴリー作成日時
     * @return DateTimeImmutable
     */
    public function getMakeDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('makeDate');
        return (new DateTimeImmutable)->setTimestamp($this->makeDate);
    }

    /**
     * 商品カテゴリー更新日時
     * @return DateTimeImmutable
     */
    public function getUpdateDate(): DateTimeImmutable
    {
        $this->assertFieldInitialized('updateDate');
        return (new DateTimeImmutable)->setTimestamp($this->updateDate);
    }
}
