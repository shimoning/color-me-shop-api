<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * OAuth 認証で利用するリダイレクト URI。
 */
enum AuthRedirectUri: string
{
    case NO_REDIRECT = 'urn:ietf:wg:oauth:2.0:oob'; // リダイレクトを使用しない
}
