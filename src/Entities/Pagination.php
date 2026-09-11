<?php

namespace Shimoning\ColorMeShopApi\Entities;

use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;

/**
 * API レスポンスのページネーション情報。
 */
class Pagination extends Entity
{
    private const REQUIRED_KEYS = ['total', 'limit', 'offset'];

    protected int $total;
    protected int $limit;
    protected int $offset;
    protected string $_responseContext;
    protected string $_metaKey;

    /**
     * API レスポンスからページネーション情報を生成する。
     *
     * @param mixed $data ページネーション情報
     * @param string $metaKey ページネーション情報が格納されているキー
     * @param string $responseContext 対象 API レスポンスを識別する文言
     * @return void
     * @throws InvalidPaginationException ページネーション情報または存在する値の型が不正な場合
     */
    public function __construct(
        mixed $data,
        string $metaKey = 'meta',
        string $responseContext = 'API レスポンス',
    ) {
        if (! \is_array($data)) {
            throw new InvalidPaginationException(\sprintf(
                '%sのページネーション情報「%s」が不正です。array を期待しましたが %s でした。',
                $responseContext,
                $metaKey,
                \get_debug_type($data),
            ));
        }

        $validationMessage = self::invalidTypeMessage($data, $metaKey, $responseContext);
        if ($validationMessage !== null) {
            throw new InvalidPaginationException($validationMessage);
        }

        if (! self::hasAllRequiredKeys($data)) {
            $this->_responseContext = $responseContext;
            $this->_metaKey = $metaKey;
        }

        parent::__construct($data);
    }

    /**
     * 全必須キーが存在するかを返す。
     *
     * @param array<string, mixed> $data
     */
    private static function hasAllRequiredKeys(array $data): bool
    {
        return \array_reduce(
            self::REQUIRED_KEYS,
            static fn(bool $hasAll, string $key): bool => $hasAll && \array_key_exists($key, $data),
            true,
        );
    }

    /**
     * 存在するページング値の型検証結果を返す。
     *
     * null は検証成功を表す。このメソッドは入力を変更しない。
     *
     * @param array<string, mixed> $data
     */
    private static function invalidTypeMessage(
        array $data,
        string $metaKey,
        string $responseContext,
    ): ?string {
        $invalidKeys = \array_values(\array_filter(
            self::REQUIRED_KEYS,
            static fn(string $key): bool => \array_key_exists($key, $data) && ! \is_int($data[$key]),
        ));
        if ($invalidKeys === []) {
            return null;
        }

        $invalidKey = $invalidKeys[0];
        return \sprintf(
            '%sのページネーション情報「%s.%s」が不正です。int を期待しましたが %s でした。',
            $responseContext,
            $metaKey,
            $invalidKey,
            \get_debug_type($data[$invalidKey]),
        );
    }

    /**
     * 合計数
     * @return int
     * @throws MissingPaginationException total が欠損している場合
     */
    public function getTotal(): int
    {
        if (! isset($this->total)) {
            throw $this->missingValueException('total');
        }

        return $this->total;
    }

    /**
     * 取得件数
     * @return int
     * @throws MissingPaginationException limit が欠損している場合
     */
    public function getLimit(): int
    {
        if (! isset($this->limit)) {
            throw $this->missingValueException('limit');
        }

        return $this->limit;
    }

    /**
     * 取得開始位置
     * @return int
     * @throws MissingPaginationException offset が欠損している場合
     */
    public function getOffset(): int
    {
        if (! isset($this->offset)) {
            throw $this->missingValueException('offset');
        }

        return $this->offset;
    }

    /**
     * 欠損したページング値を表す例外を生成する。
     */
    private function missingValueException(string $key): MissingPaginationException
    {
        return new MissingPaginationException(\sprintf(
            '%sにページネーション情報「%s.%s」がありません。ページング値を取得できません。',
            $this->_responseContext,
            $this->_metaKey,
            $key,
        ));
    }
}
