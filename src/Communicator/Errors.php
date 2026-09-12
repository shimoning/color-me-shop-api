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

        // 配列でない要素は除外する。元の Response は保持するため、
        // 呼び出し側は HTTP ステータスと生のレスポンスから詳細を確認できる。
        $items = \array_values(\array_filter($items, static fn(mixed $item): bool => \is_array($item)));

        return new self(
            $response,
            \array_map(function (array $error) {
                return new Error($error);
            }, $items),
        );
    }
}
