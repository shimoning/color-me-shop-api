<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

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
     * @return string
     */
    public function getTokenType(): string
    {
        $this->assertFieldInitialized('tokenType');

        return $this->tokenType;
    }

    /**
     * アプリが利用したい機能
     * @return array<AuthScope>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * 作成日
     * @return int
     */
    public function getCreatedAt(): int
    {
        $this->assertFieldInitialized('createdAt');

        return $this->createdAt;
    }
}
