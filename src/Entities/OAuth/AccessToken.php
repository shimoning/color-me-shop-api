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
     * 公式 OpenAPI との差分: 応答例は `bearer` だが、実 API は `Bearer` を返す (2026-09-29)。
     *
     * @return string
     * @see docs/api-unixtime-observation.md
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
     * 公式 OpenAPI との差分: 応答例にない `created_at` を実 API は unixtime で返す (2026-09-29)。
     *
     * @return DateTimeImmutable
     * @see docs/api-unixtime-observation.md
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        $this->assertFieldInitialized('createdAt');

        return (new DateTimeImmutable)->setTimestamp($this->createdAt);
    }
}
