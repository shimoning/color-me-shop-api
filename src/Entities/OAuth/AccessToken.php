<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\AuthScope;

/**
 * OAuth 認証で発行されたアクセストークン情報。
 */
class AccessToken extends Entity
{
    protected string $accessToken;
    protected string $tokenType;
    protected ?string $scope;
    protected int $createdAt;
    /** @var list<AuthScope> */
    protected array $scopes = [];

    /**
     * トークンリザルト
     *
     * @param array{access_token: string, token_type: string, scope?: string|null, created_at: int} $accessToken
     * @return void
     */
    public function __construct(array $accessToken)
    {
        parent::__construct($accessToken);
        $this->parseScopes();
    }

    /**
     * scope をパースする
     *
     * @return void
     */
    protected function parseScopes()
    {
        $scopes = \explode(' ', $this->scope ?? '');
        foreach ($scopes as $scope) {
            $scope = AuthScope::tryFrom($scope);
            if ($scope) {
                $this->scopes[] = $scope;
            }
        }
    }

    /**
     * アクセストークンを取得
     * @return string
     */
    public function getAccessToken(): string
    {
        $this->assertFieldInitialized('accessToken');

        return $this->accessToken;
    }

    /**
     * トークン種別を取得
     *
     * 公式ドキュメントの応答例は `bearer` だが、2026-09-29 の実測では `Bearer` が返った。
     * 本ライブラリはこの値を比較しないため実害はなく、Authorization ヘッダは
     * Communicator\RequestOptions が常に `Bearer ` を前置して組み立てる。
     * @return string
     */
    public function getTokenType(): string
    {
        $this->assertFieldInitialized('tokenType');

        return $this->tokenType;
    }

    /**
     * アプリが利用したい機能
     * @return list<AuthScope>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * トークンの発行日時
     *
     * 公式 OpenAPI にトークンエンドポイントの formal schema はなく、`info.description` の
     * 応答例にも `created_at` の記載はない。2026-09-29 に認可フローを実行して実測し、
     * 実 API が unixtime で返すことを確認した。
     * 出典: docs/api-unixtime-observation.md
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        $this->assertFieldInitialized('createdAt');

        return (new DateTimeImmutable)->setTimestamp($this->createdAt);
    }
}
