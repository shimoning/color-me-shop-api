<?php

namespace Shimoning\ColorMeShopApi\Entities\Output;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\AuthScope;

class AccessToken extends Entity
{
    private string $_accessToken;
    private string $_tokenType;
    private string $_scope;
    private array $_scopes = [];

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
        $scopes = \explode(' ', $this->_scope);
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
}
