<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\AuthScope;

class AccessToken extends Entity
{
    protected string $_accessToken;
    protected string $_tokenType;
    protected ?string $_scope;
    protected int $_createdAt;
    protected array $_scopes = [];

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
        $scopes = \explode(' ', $this->_scope ?? '');
        foreach ($scopes as $scope) {
            $_scope = AuthScope::tryFrom($scope);
            if ($_scope) {
                $this->_scopes[] = $_scope;
            }
        }
    }

    /**
     * アクセストークンを取得
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->_accessToken;
    }

    /**
     * トークン種別を取得
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->_tokenType;
    }

    /**
     * アプリが利用したい機能
     * @return array
     */
    public function getScopes(): array
    {
        return $this->_scopes;
    }

    /**
     * 作成日
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->_createdAt;
    }
}
