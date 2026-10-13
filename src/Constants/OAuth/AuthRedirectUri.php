<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\OAuth;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * OAuth 認証で利用するリダイレクト URI。
 */
enum AuthRedirectUri: string
{
    case NO_REDIRECT = 'urn:ietf:wg:oauth:2.0:oob'; // リダイレクトを使用しない
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(AuthRedirectUri::class);
