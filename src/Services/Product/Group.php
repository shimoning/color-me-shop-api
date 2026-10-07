<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\Group\Group as GroupEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品グループ API を操作するサービス。 */
class Group extends Service
{
    /**
     * 商品グループ単体。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function one(int|string $id, ?string $accessToken = null): GroupEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/groups/' . $id));
        return $this->_handle($response, static fn(?array $data): GroupEntity => new GroupEntity($data['group'] ?? []));
    }

    /**
     * 商品グループ一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
     * @param string|null $accessToken
     * @return Collection<GroupEntity>|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/groups'));
        return $this->_handle(
            $response,
            static fn(?array $data): Collection => Collection::cast(GroupEntity::class, $data['groups'] ?? []),
        );
    }

    /**
     * 商品グループを作成する。成功は 201 で、応答の `group` を返す。
     *
     * `display_state` は `showing` / `hidden` / `members_only` に限り、応答専用の2値は構築時に拒否する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function create(GroupInput $input, ?string $accessToken = null): GroupEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/groups'),
            ['group' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): GroupEntity => new GroupEntity($data['group'] ?? []));
    }

    /**
     * 商品グループを更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     *
     * 公式 OpenAPI の更新 request に `parent_group_id` はない (作成専用)。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(int|string $id, GroupInput $input, ?string $accessToken = null): GroupEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/groups/' . $id),
            ['group' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): GroupEntity => new GroupEntity($data['group'] ?? []));
    }
}
