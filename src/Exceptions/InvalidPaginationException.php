<?php

namespace Shimoning\ColorMeShopApi\Exceptions;

/**
 * API レスポンスのページネーション情報の値または型が不正な場合の例外。
 *
 * meta が配列でない場合、または存在する必須値 (total / limit / offset) が
 * null や int 以外の場合に投げられる。
 */
class InvalidPaginationException extends ColorMeApiException {}
