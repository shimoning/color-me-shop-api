<?php

namespace Shimoning\ColorMeShopApi\Communicator;

use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Error;

/**
 * API エラーと元のレスポンスを保持するコレクション。
 *
 * status、headers などの応答情報は NoContent と同じく getResponse() 経由で参照する。
 *
 * @extends Collection<Error>
 * @see docs/implementation-notes.md
 */
class Errors extends Collection
{
    private const ERROR_KEYS = [
        'code' => true,
        'message' => true,
        'status' => true,
        'field' => true,
    ];

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
     * ワイヤ上で object 形状の要素は、既知フィールドが空または不正でも Error として保持する。
     * 不正な既知フィールドは欠損として扱い、構築に失敗した要素だけをスキップする。
     *
     * @param Response $response API レスポンス
     * @return self
     * @see docs/api-error-responses.md
     */
    static public function build(Response $response): self
    {
        $errors = [];
        foreach (self::errorItems($response) as $item) {
            $data = self::objectData($item);
            if ($data === null) {
                continue;
            }

            try {
                $errors[] = self::buildError(self::normalizeErrorData($data));
            } catch (\Throwable) {
                continue;
            }
        }

        return new self($response, $errors);
    }

    /**
     * object / list の形状を失わないよう、生 JSON からエラー要素を取得する。
     *
     * @return array<array-key, mixed>
     */
    private static function errorItems(Response $response): array
    {
        try {
            $body = \json_decode($response->getRawBody(), false, 512, \JSON_THROW_ON_ERROR);
            if ($body instanceof \stdClass) {
                return \property_exists($body, 'errors') && \is_array($body->errors)
                    ? $body->errors
                    : [];
            }
        } catch (\JsonException) {
            // テストダブルなど生 JSON を持たない Response は、従来のパース結果へフォールバックする。
        }

        $parsedBody = $response->getParsedBody();
        $items = \is_array($parsedBody) ? ($parsedBody['errors'] ?? []) : [];

        return \is_array($items) ? $items : [];
    }

    /**
     * ワイヤ上の object を Error の入力へ変換する。
     *
     * @return array<array-key, mixed>|null list またはスカラーなら null
     */
    private static function objectData(mixed $item): ?array
    {
        if ($item instanceof \stdClass) {
            /** @var array<array-key, mixed> */
            return self::objectValueToArray($item);
        }

        // 生 JSON を利用できない Response のフォールバック。空配列は object と区別できないため除外する。
        return \is_array($item) && ! \array_is_list($item) ? $item : null;
    }

    /**
     * 生 JSON から復元した object を、ネストを含めて従来の連想配列表現へ変換する。
     *
     * @return mixed
     */
    private static function objectValueToArray(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $data = [];
            foreach (\get_object_vars($value) as $key => $item) {
                $data[$key] = self::objectValueToArray($item);
            }

            return $data;
        }

        if (\is_array($value)) {
            return \array_map(self::objectValueToArray(...), $value);
        }

        return $value;
    }

    /**
     * 既知4フィールドだけを hydrate し、追加プロパティは getRaw() のみに保持する。
     *
     * @param array<array-key, mixed> $rawData
     */
    private static function buildError(array $rawData): Error
    {
        /** @var array<string, mixed> $hydrateData */
        $hydrateData = \array_intersect_key($rawData, self::ERROR_KEYS);
        $error = new Error($hydrateData);

        // Entity の公開 API と src/Entities/ を変更せず、hydrate 入力と raw 表現を分離する。
        // PHP 8.1 以降は private プロパティも ReflectionProperty から直接設定できる。
        static $rawProperty = null;
        $rawProperty ??= new \ReflectionProperty(Entity::class, '_raw');
        $rawProperty->setValue($error, $rawData);

        return $error;
    }

    /**
     * 既知フィールドを個別に検証し、有効なフィールドだけを Error へ渡す。
     *
     * integer の code は公開 getter の契約に合わせて string へ正規化する。
     *
     * @param array<array-key, mixed> $item
     * @return array<array-key, mixed>
     */
    private static function normalizeErrorData(array $item): array
    {
        if (\array_key_exists('code', $item)) {
            if (\is_int($item['code'])) {
                $item['code'] = (string) $item['code'];
            } else if (! \is_string($item['code'])) {
                unset($item['code']);
            }
        }

        if (\array_key_exists('message', $item) && ! \is_string($item['message'])) {
            unset($item['message']);
        }
        if (\array_key_exists('status', $item) && ! \is_int($item['status'])) {
            unset($item['status']);
        }
        if (\array_key_exists('field', $item) && ! \is_string($item['field'])) {
            unset($item['field']);
        }

        return $item;
    }
}
