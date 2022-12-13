<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\AuthScope;

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
     * @param array{access_token: string, token_type: string, scope: string} $accessToken
     */
    public function __construct(array $accessToken)
    {
        parent::__construct($accessToken);
        $this->parseScopes();
    }

    /**
     * scope をパースする
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
        return $this->accessToken;
    }

    /**
     * トークン種別を取得
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * アプリが利用したい機能
     * @return array
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
        return $this->createdAt;
    }
}
