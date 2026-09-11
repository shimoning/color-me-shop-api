<?php

namespace Shimoning\ColorMeShopApi\Entities;

use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;

/**
 * ページネーション情報を持つ型付きコレクション。
 *
 * @template T
 * @extends Collection<T>
 * @phpstan-consistent-constructor
 */
class Page extends Collection
{
    protected ?Pagination $_pagination;
    protected string $_paginationContext;
    protected string $_paginationKey;

    /**
     * API レスポンスからページを生成する。
     *
     * @template TEntity of object
     * @param class-string<TEntity> $class 要素として生成するエンティティクラス
     * @param mixed $data API レスポンスの配列
     * @param string $key 要素が格納されているキー
     * @param string $metaKey ページネーション情報が格納されているキー
     * @param string $responseContext 対象 API レスポンスを識別する文言
     * @return static<TEntity>
     */
    public static function build(
        string $class,
        mixed $data,
        string $key,
        string $metaKey = 'meta',
        string $responseContext = 'API レスポンス',
    ): static {
        $meta = $data[$metaKey] ?? null;

        return new static(
            Collection::cast($class, $data[$key] ?? []),
            $meta === null ? null : new Pagination($meta, $metaKey, $responseContext),
            $responseContext,
            $metaKey,
        );
    }

    /**
     * ページを生成する。
     *
     * @param mixed $items ページに格納する要素
     * @param Pagination|null $pagination ページネーション情報
     * @param string $responseContext 対象 API レスポンスを識別する文言
     * @param string $paginationKey ページネーション情報が格納されているキー
     * @return void
     */
    public function __construct(
        mixed $items,
        ?Pagination $pagination,
        string $responseContext = 'API レスポンス',
        string $paginationKey = 'meta',
    ) {
        parent::__construct($items);
        $this->_pagination = $pagination;

        if ($pagination === null) {
            $this->_paginationContext = $responseContext;
            $this->_paginationKey = $paginationKey;
        }
    }

    /**
     * 合計数
     * @return int
     * @throws MissingPaginationException ページネーション情報または total が欠損している場合
     */
    public function getTotal(): int
    {
        return $this->pagination()->getTotal();
    }

    /**
     * 取得件数
     * @return int
     * @throws MissingPaginationException ページネーション情報または limit が欠損している場合
     */
    public function getLimit(): int
    {
        return $this->pagination()->getLimit();
    }

    /**
     * 取得開始位置
     * @return int
     * @throws MissingPaginationException ページネーション情報または offset が欠損している場合
     */
    public function getOffset(): int
    {
        return $this->pagination()->getOffset();
    }

    /**
     * ページネーション情報を取得する。
     *
     * @throws MissingPaginationException API レスポンスにページネーション情報がない場合
     */
    private function pagination(): Pagination
    {
        if ($this->_pagination !== null) {
            return $this->_pagination;
        }

        throw new MissingPaginationException(\sprintf(
            '%sにページネーション情報「%s」がありません。ページング値を取得できません。',
            $this->_paginationContext,
            $this->_paginationKey,
        ));
    }
}
