<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Error;

/**
 * API エラーと元のレスポンスを保持するコレクション。
 *
 * @extends Collection<Error>
 */
class Errors extends Collection
{
    private const REQUIRED_ERROR_KEYS = ['code', 'message', 'status'];

    private Response $_response;

    /**
     * エラーコレクションを生成する。
     *
     * @param Response $response 元の API レスポンス
     * @param mixed $items エラー要素
     * @return void
     */
    public function __construct(Response $response, mixed $items = [])
    {
        parent::__construct($items);
        $this->_response = $response;
    }

    /**
     * 元の API レスポンスを取得する。
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->_response;
    }

    /**
     * API レスポンスからエラーコレクションを生成する。
     *
     * @param Response $response API レスポンス
     * @return self
     */
    static public function build(Response $response): self
    {
        $parsedBody = $response->getParsedBody();
        $items = \is_array($parsedBody) ? ($parsedBody['errors'] ?? []) : [];
        if (! \is_array($items)) {
            $items = [];
        }

        $errors = [];
        foreach ($items as $item) {
            if (! \is_array($item) || ! self::isUsableErrorData($item)) {
                continue;
            }

            try {
                $errors[] = new Error($item);
            } catch (\Throwable) {
                // 構築できない要素だけを除外する。元の Response は保持するため、
                // 呼び出し側は HTTP ステータスと生のレスポンスから詳細を確認できる。
                continue;
            }
        }

        return new self($response, $errors);
    }

    /**
     * 全必須 getter を安全に利用できる Error の構築候補か判定する。
     *
     * 数値キーを含む配列は API のエラーオブジェクトではなくリスト形状として除外する。
     * 必須キーが揃わない要素も、不完全な Error が利用時に二次例外を起こすため除外する。
     *
     * @param array<array-key, mixed> $item
     */
    private static function isUsableErrorData(array $item): bool
    {
        foreach (\array_keys($item) as $key) {
            if (! \is_string($key)) {
                return false;
            }
        }

        foreach (self::REQUIRED_ERROR_KEYS as $key) {
            if (! \array_key_exists($key, $item)) {
                return false;
            }
        }

        return true;
    }
}
