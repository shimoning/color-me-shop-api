<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * OAuth 認証で要求するスコープの集合。
 */
class Scopes implements Value
{
    /** @var array<string> */
    private array $_scopes;

    /**
     * @param array<AuthScope|string> $scopes
     * @return void
     * @throws ParameterException 未対応のスコープが含まれる場合
     */
    public function __construct(array $scopes)
    {
        foreach ($scopes as $scope) {
            if ($scope instanceof AuthScope) {
                $this->_scopes[] = $scope->value;
                continue;
            } else if (\is_string($scope)) {
                $authScope = AuthScope::tryFrom($scope);
                if ($authScope instanceof AuthScope) {
                    $this->_scopes[] = $scope;
                    continue;
                }
            }
            throw new ParameterException('scope は、文字列か AuthScope 型を設定してください。');
        }
    }

    /**
     * 文字列化した scope を取得
     * @return string
     */
    public function get(): string
    {
        return \implode(' ', $this->_scopes);
    }

    /**
     * スコープを検証する。
     *
     * @param mixed $scopes 検証する値
     * @return bool
     */
    public function validate($scopes): bool
    {
        // TODO: merge construct
        return true;
    }
}
