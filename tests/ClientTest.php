<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters as SalesSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\Scopes;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class ClientTest extends TestCase
{
    // --- アクセストークン未指定 ---------------------------------------------

    /**
     * @return array<string, array{\Closure(Client, ?string): void}>
     */
    public static function accessTokenApiProvider(): array
    {
        return [
            'getShop' => [static function (Client $client, ?string $accessToken): void {
                $client->getShop($accessToken);
            }],
            'getSales' => [static function (Client $client, ?string $accessToken): void {
                $client->getSales(null, $accessToken);
            }],
            'statSales' => [static function (Client $client, ?string $accessToken): void {
                $client->statSales(new \DateTimeImmutable('2024-01-01'), $accessToken);
            }],
            'getSale' => [static function (Client $client, ?string $accessToken): void {
                $client->getSale(1001, $accessToken);
            }],
            'updateSale' => [static function (Client $client, ?string $accessToken): void {
                $client->updateSale(new SaleUpdater(['id' => 1001]), $accessToken);
            }],
            'cancelSale' => [static function (Client $client, ?string $accessToken): void {
                $client->cancelSale(1001, false, $accessToken);
            }],
            'sendSalesMail' => [static function (Client $client, ?string $accessToken): void {
                $client->sendSalesMail(1001, MailType::PAID, $accessToken);
            }],
            'getPayments' => [static function (Client $client, ?string $accessToken): void {
                $client->getPayments($accessToken);
            }],
            'getDeliveries' => [static function (Client $client, ?string $accessToken): void {
                $client->getDeliveries($accessToken);
            }],
            'getCustomers' => [static function (Client $client, ?string $accessToken): void {
                $client->getCustomers(null, $accessToken);
            }],
            'getCustomer' => [static function (Client $client, ?string $accessToken): void {
                $client->getCustomer(501, $accessToken);
            }],
            'getProductGroups' => [static function (Client $client, ?string $accessToken): void {
                $client->getProductGroups($accessToken);
            }],
            'getProductCategories' => [static function (Client $client, ?string $accessToken): void {
                $client->getProductCategories($accessToken);
            }],
        ];
    }

    /**
     * @param \Closure(Client, ?string): void $callApi
     */
    private function assertAccessTokenRejectedWithoutHttpRequest(
        Client $client,
        HttpMock $mock,
        \Closure $callApi,
        ?string $accessToken,
    ): void {
        try {
            $callApi($client, $accessToken);
            $this->fail('アクセストークンなしで API 呼び出しが受理された');
        } catch (ParameterException $exception) {
            $this->assertSame('アクセストークンは必ず指定してください', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        }
    }

    /**
     * 空文字のトークンを明示的に渡した場合、黙って以前のトークンに
     * フォールバックせず、HTTP 送信前に ParameterException になること。
     *
     * @param \Closure(Client, ?string): void $callApi
     */
    #[DataProvider('accessTokenApiProvider')]
    public function test_全公開APIで空文字のトークンは送信前に拒否される(\Closure $callApi): void
    {
        $mock = HttpMock::json(200, '{}');
        $client = new Client('tenant-A-token', $mock->client());

        $this->assertAccessTokenRejectedWithoutHttpRequest($client, $mock, $callApi, '');
    }

    /**
     * アクセストークンを持たない Client で API を呼んだ場合、PHP の Error ではなく
     * HTTP 送信前に意味のある ParameterException になること。
     *
     * @param \Closure(Client, ?string): void $callApi
     */
    #[DataProvider('accessTokenApiProvider')]
    public function test_全公開APIでトークン未指定は送信前に拒否される(\Closure $callApi): void
    {
        $mock = HttpMock::json(200, '{}');
        $client = new Client(null, $mock->client());

        $this->assertAccessTokenRejectedWithoutHttpRequest($client, $mock, $callApi, null);
    }

    /**
     * アクセストークンは不透明な文字列であり、空文字の定義は厳密に "" のみとするため、
     * PHP の真偽値判定では false になる "0" も有効な値として扱う。
     */
    public function test_ゼロ文字列のトークンを有効として送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));
        $client = new Client('tenant-A-token', $mock->client());

        $client->getShop('0');

        $this->assertSame('Bearer 0', $mock->header('Authorization'));
    }

    public function test_コンストラクタに空文字を渡してもトークン未指定として扱う(): void
    {
        $this->expectException(ParameterException::class);

        (new Client(''))->getShop();
    }

    // --- インスタンス間の独立性 ---------------------------------------------

    /**
     * 別々の Client インスタンスが、それぞれ自分のアクセストークンで通信すること。
     * salesService() の内部キャッシュがインスタンスをまたいで共有されないことの回帰テスト。
     */
    public function test_別のClientインスタンスは自分のトークンで通信する(): void
    {
        $mockA = HttpMock::json(200, self::fixture('sale.json'));
        $mockB = HttpMock::json(200, self::fixture('sale.json'));

        (new Client('token-A', $mockA->client()))->getSale(1001);
        (new Client('token-B', $mockB->client()))->getSale(1001);

        $this->assertSame('Bearer token-A', $mockA->header('Authorization'));
        $this->assertSame('Bearer token-B', $mockB->header('Authorization'));
    }

    public function test_同じClientで複数回呼んでもトークンを引き継ぐ(): void
    {
        $mock = new HttpMock([
            new \GuzzleHttp\Psr7\Response(200, [], self::fixture('sale.json')),
            new \GuzzleHttp\Psr7\Response(200, [], self::fixture('sale.json')),
        ]);

        $client = new Client('my-token', $mock->client());
        $client->getSale(1001);
        $client->getSale(1002);

        $this->assertSame(2, $mock->countRequests());
        $this->assertSame('Bearer my-token', $mock->header('Authorization', 0));
        $this->assertSame('Bearer my-token', $mock->header('Authorization', 1));
    }

    // --- 各メソッドの委譲 ---------------------------------------------------

    public function test_getShopはショップ情報を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        $shop = (new Client('my-token', $mock->client()))->getShop();

        $this->assertSame('my-shop', $shop->getId());
        $this->assertSame('https://api.shop-pro.jp/v1/shop', $mock->uri());
    }

    public function test_getSalesは受注一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        $page = (new Client('my-token', $mock->client()))->getSales();

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(2, $page->count());
        $this->assertSame('https://api.shop-pro.jp/v1/sales', $mock->uri());
    }

    public function test_getSalesは検索条件を渡せる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        (new Client('my-token', $mock->client()))
            ->getSales(new SalesSearchParameters(['limit' => 30, 'offset' => 60]));

        $this->assertSame(['limit' => '30', 'offset' => '60'], $mock->query());
    }

    public function test_getSaleは受注を1件取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $sale = (new Client('my-token', $mock->client()))->getSale(1001);

        $this->assertSame(1001, $sale->getId());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001', $mock->uri());
    }

    public function test_statSalesは売上集計を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_stat.json'));

        $stat = (new Client('my-token', $mock->client()))->statSales(new \DateTimeImmutable('2024-01-01'));

        $this->assertSame(12000, $stat->getAmountToday());
        $this->assertStringContainsString('make_date=2024-01-01', $mock->uri());
    }

    public function test_updateSaleは受注を更新する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Client('my-token', $mock->client()))
            ->updateSale(new SaleUpdater(['id' => 1001, 'paid' => true, 'point_state' => 'fixed']));

        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001', $mock->uri());
        $this->assertSame(['sale' => ['id' => 1001, 'paid' => true, 'point_state' => 'fixed']], $mock->jsonBody());
    }

    public function test_cancelSaleは受注をキャンセルする(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Client('my-token', $mock->client()))->cancelSale(1001, true);

        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001/cancel', $mock->uri());
        $this->assertSame(['restock' => true], $mock->jsonBody());
    }

    public function test_sendSalesMailはメールを送信する(): void
    {
        $mock = HttpMock::json(200, '{}');

        $result = (new Client('my-token', $mock->client()))->sendSalesMail(1001, MailType::PAID);

        $this->assertTrue($result);
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001/mails', $mock->uri());
        $this->assertSame(['mail' => ['type' => 'paid']], $mock->jsonBody());
    }

    public function test_getPaymentsは決済設定を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('payments.json'));

        $payments = (new Client('my-token', $mock->client()))->getPayments();

        $this->assertInstanceOf(Collection::class, $payments);
        $this->assertSame(2, $payments->count());
        $this->assertSame('https://api.shop-pro.jp/v1/payments', $mock->uri());
    }

    public function test_getDeliveriesは配送方法を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('deliveries.json'));

        $deliveries = (new Client('my-token', $mock->client()))->getDeliveries();

        $this->assertSame(1, $deliveries->count());
        $this->assertSame('https://api.shop-pro.jp/v1/deliveries', $mock->uri());
    }

    public function test_getCustomersは顧客一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customers_page.json'));

        $page = (new Client('my-token', $mock->client()))->getCustomers();

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(2, $page->count());
        $this->assertSame('https://api.shop-pro.jp/v1/customers', $mock->uri());
    }

    public function test_getCustomersは検索条件を渡せる(): void
    {
        $mock = HttpMock::json(200, self::fixture('customers_page.json'));

        (new Client('my-token', $mock->client()))
            ->getCustomers(new CustomerSearchParameters(['limit' => 5]));

        $this->assertSame(['limit' => '5'], $mock->query());
    }

    public function test_getCustomerは顧客を1件取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        $customer = (new Client('my-token', $mock->client()))->getCustomer(501);

        $this->assertSame(501, $customer->getId());
        $this->assertSame('https://api.shop-pro.jp/v1/customers/501', $mock->uri());
    }

    public function test_getProductGroupsは商品グループを取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('groups.json'));

        $groups = (new Client('my-token', $mock->client()))->getProductGroups();

        $this->assertSame(2, $groups->count());
        $this->assertSame('https://api.shop-pro.jp/v1/groups', $mock->uri());
    }

    public function test_getProductCategoriesは商品カテゴリーを取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('categories.json'));

        $categories = (new Client('my-token', $mock->client()))->getProductCategories();

        $this->assertSame(1, $categories->count());
        $this->assertSame('https://api.shop-pro.jp/v1/categories', $mock->uri());
    }

    // --- OAuth --------------------------------------------------------------

    public function test_getOAuthUrlは認可URLを組み立てる(): void
    {
        $options = new OAuthOptions('my-client-id', 'my-secret', 'https://example.test/callback');

        $url = (new Client())->getOAuthUrl($options, new Scopes([AuthScope::READ_SALES]));

        $this->assertStringStartsWith('https://api.shop-pro.jp/oauth/authorize?', $url);
        $this->assertStringContainsString('client_id=my-client-id', $url);
        $this->assertStringContainsString('scope=read_sales', $url);
    }

    public function test_exchangeCode2Tokenはトークンに交換する(): void
    {
        $mock = HttpMock::json(200, self::fixture('oauth_token.json'));
        $options = new OAuthOptions('my-client-id', 'my-secret', 'https://example.test/callback');

        $token = (new Client(null, $mock->client()))->exchangeCode2Token($options, 'auth-code');

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame('dummy-access-token', $token->getAccessToken());
        $this->assertSame('https://api.shop-pro.jp/oauth/token', $mock->uri());
    }

    public function test_OAuthはアクセストークンなしでも使える(): void
    {
        $mock = HttpMock::json(200, self::fixture('oauth_token.json'));
        $options = new OAuthOptions('id', 'secret', 'https://example.test/cb');

        $this->assertInstanceOf(
            AccessToken::class,
            (new Client(null, $mock->client()))->exchangeCode2Token($options, 'code'),
        );
    }

    // --- アクセストークンの引き回し -------------------------------------------

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        (new Client('my-token', $mock->client()))->getShop('override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }

    /**
     * getSale() は salesService() と Sales::one() の両方にトークンを渡している。
     * getCustomer() も同様に Customer のコンストラクタとメソッドの両方に渡している。
     * 二重に渡していても最終的に使われるトークンが一致することを固定する。
     */
    public function test_トークンを二重に渡す経路でも引数のトークンが使われる(): void
    {
        $saleMock = HttpMock::json(200, self::fixture('sale.json'));
        $customerMock = HttpMock::json(200, self::fixture('customer.json'));

        (new Client('my-token', $saleMock->client()))->getSale(1001, 'override-token');
        (new Client('my-token', $customerMock->client()))->getCustomer(501, 'override-token');

        $this->assertSame('Bearer override-token', $saleMock->header('Authorization'));
        $this->assertSame('Bearer override-token', $customerMock->header('Authorization'));
    }

    public function test_コンストラクタで渡さず引数だけでも呼べる(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        (new Client(null, $mock->client()))->getShop('runtime-token');

        $this->assertSame('Bearer runtime-token', $mock->header('Authorization'));
    }

    /**
     * 引数で渡したアクセストークンはインスタンスに保持され、以降の呼び出しでも使われる。
     */
    public function test_引数で渡したトークンは以降の呼び出しにも引き継がれる(): void
    {
        $mock = new HttpMock([
            new \GuzzleHttp\Psr7\Response(200, [], self::fixture('shop.json')),
            new \GuzzleHttp\Psr7\Response(200, [], self::fixture('shop.json')),
        ]);

        $client = new Client('initial-token', $mock->client());
        $client->getShop('new-token');
        $client->getShop();

        $this->assertSame('Bearer new-token', $mock->header('Authorization', 0));
        $this->assertSame('Bearer new-token', $mock->header('Authorization', 1));
    }

    // --- エラーレスポンス ------------------------------------------------------

    public function test_エラーレスポンスはErrorsとして返る(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $errors = (new Client('my-token', $mock->client()))->getShop();

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame('401010', $errors[0]->getCode());
    }
}
