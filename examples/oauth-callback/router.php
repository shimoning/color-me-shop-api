<?php

/**
 * OAuth のコールバックを localhost で受け取り、トークン応答を確認するサンプル。
 *
 * 認可コードは 1 度しか交換できず、ブラウザでの承認を挟むため、通常のスクリプトでは
 * トークン応答を観測しにくい。このサンプルはその確認を手元で行うためのもので、
 * ライブラリの動作確認や、API の応答が変わっていないかの点検に使える。
 *
 * 使い方:
 *
 *   1. カラーミーのアプリ設定に、下記のリダイレクト URI を登録する（完全一致）
 *   2. プロジェクト直下の .env に CLIENT_ID と CLIENT_SECRET を設定する
 *   3. composer oauth:callback を実行する
 *   4. ブラウザで http://localhost:8765/ を開き、案内に従って承認する
 *
 * 環境変数:
 *
 *   OAUTH_CALLBACK_SCOPES 要求スコープをカンマ区切りで（既定 read_products,read_sales）
 *
 * 待ち受けポートは composer スクリプトで 8765 に固定している。変更する場合は
 * composer.json の oauth:callback と、カラーミーに登録するリダイレクト URI の両方を直すこと。
 *
 * このサンプルは client secret とアクセストークンの値を画面に出さない。
 * access_token は先頭 4 文字と長さだけを表示する。
 *
 * CSRF 対策として state を生成してセッションに保存し、Services\OAuth::getUrl() へ渡す。
 * コールバックでは、返された state をセッションに保存した値と照合している。
 */

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Examples\OAuthCallback;

use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Values\Scopes;

$root = \dirname(__DIR__, 2);
require_once $root . '/vendor/autoload.php';

if (\file_exists($root . '/.env')) {
    \Dotenv\Dotenv::createImmutable($root)->load();
}

// composer スクリプトの `php -S 127.0.0.1:8765` と揃える。
const PORT = 8765;
$redirectUri = 'http://localhost:' . PORT . '/callback';

\session_start();

$clientId = (string) ($_ENV['CLIENT_ID'] ?? '');
$clientSecret = (string) ($_ENV['CLIENT_SECRET'] ?? '');

\header('Content-Type: text/html; charset=utf-8');

if ($clientId === '' || $clientSecret === '') {
    \http_response_code(500);
    echo '<h1>設定が足りません</h1><p>プロジェクト直下の <code>.env</code> に <code>CLIENT_ID</code> と <code>CLIENT_SECRET</code> を設定してください。</p>';
    return true;
}

$options = new Options($clientId, $clientSecret, $redirectUri);
$path = \parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH) ?: '/';
$escape = static fn(string $v): string => \htmlspecialchars($v, \ENT_QUOTES, 'UTF-8');

if ($path === '/') {
    $names = \array_filter(\array_map('trim', \explode(',', (string) ($_ENV['OAUTH_CALLBACK_SCOPES'] ?? 'read_products,read_sales'))));
    $scopes = [];
    foreach ($names as $name) {
        $case = AuthScope::tryFrom($name);
        if ($case === null) {
            \http_response_code(500);
            echo '<h1>不明なスコープ</h1><p><code>' . $escape($name) . '</code> は <code>Constants\AuthScope</code> に定義されていません。</p>';
            return true;
        }
        $scopes[] = $case;
    }

    $state = \bin2hex(\random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $url = (new OAuth($options))->getUrl(new Scopes($scopes), $state);

    echo '<h1>OAuth コールバックのサンプル</h1>';
    echo '<p>リダイレクト URI: <code>' . $escape($redirectUri) . '</code> (ポートは固定)</p>';
    echo '<p>要求スコープ: <code>' . $escape(\implode(' ', $names)) . '</code></p>';
    echo '<p><a href="' . $escape($url) . '">認可画面を開く</a></p>';
    echo '<p>承認するとこのサーバーに戻り、トークン応答の構造を表示します。認可コードは 1 度しか交換できないため、結果の画面を再読み込みすると失敗します。</p>';
    return true;
}

if ($path !== '/callback') {
    \http_response_code(404);
    echo '<h1>404</h1>';
    return true;
}

// 認可応答はエラーのときも state を含む（RFC 6749 §4.1.2.1）。成功・失敗のどちらも
// 検証してから内容を扱う。エラーを先に表示すると、未検証の応答を画面に出したうえ
// セッションの state も消費されずに残る。
$expectedState = (string) ($_SESSION['oauth_state'] ?? '');
$givenState = (string) ($_GET['state'] ?? '');
unset($_SESSION['oauth_state']);

if ($expectedState === '' || ! \hash_equals($expectedState, $givenState)) {
    \http_response_code(400);
    echo '<h1>state が一致しません</h1>';
    echo '<p>この画面を直接開いたか、別の認可フローの応答が混入した可能性があります。'
        . ' コードは交換していません。<a href="/">最初からやり直す</a></p>';
    return true;
}

if (isset($_GET['error'])) {
    echo '<h1>認可エラー</h1>';
    echo '<p>error: <code>' . $escape((string) $_GET['error']) . '</code></p>';
    echo '<p>' . $escape((string) ($_GET['error_description'] ?? '')) . '</p>';
    return true;
}

$code = (string) ($_GET['code'] ?? '');
if ($code === '') {
    \http_response_code(400);
    echo '<h1>認可コードがありません</h1>';
    return true;
}

// Entity を通さない生の応答を見るため、トークンエンドポイントを直接叩く。
$response = (new Request(new RequestOptions(['form' => true])))->post(
    $options->getEndpointUri() . '/token',
    [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
        'code' => $code,
    ],
);

$status = $response->getStatus();
$parsed = $response->getParsedBody();

echo '<h1>トークン応答</h1>';
echo '<p>HTTP: <code>' . $status . '</code></p>';

if (! \is_array($parsed)) {
    echo '<p>JSON として解釈できませんでした。</p>';
    return true;
}

$types = [];
$masked = [];
foreach ($parsed as $key => $value) {
    $types[$key] = \get_debug_type($value);
    $masked[$key] = \in_array($key, ['access_token', 'refresh_token'], true) && \is_string($value)
        ? \substr($value, 0, 4) . '…(' . \strlen($value) . ' 文字)'
        : $value;
}

echo '<p>キー: <code>' . $escape(\implode(', ', \array_keys($parsed))) . '</code></p>';
echo '<p>型: <code>' . $escape(\json_encode($types, \JSON_UNESCAPED_UNICODE) ?: '') . '</code></p>';
echo '<pre>' . $escape(\json_encode($masked, \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT) ?: '') . '</pre>';

if ($status === 200) {
    echo '<p>発行したアクセストークンはカラーミー側で有効なまま残ります。不要であれば'
        . ' <a href="https://admin.shop-pro.jp/?mode=app_use_lst">許可済みアプリ一覧</a> から失効させてください。</p>';
}

return true;
