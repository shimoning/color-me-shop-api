<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Sales;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Sale\Sale;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\Stat\Stat;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class SalesTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function createFields(array $overrides = []): array
    {
        return \array_merge([
            'details' => [['product_id' => 101, 'product_num' => 2]],
            'payment_id' => 3,
        ], $overrides);
    }

    // --- page -------------------------------------------------------------

    public function test_受注一覧をPageとして取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        $page = (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(2, $page->count());
        $this->assertContainsOnlyInstancesOf(Sale::class, $page->all());
        $this->assertSame([1001, 1002], \array_map(fn($s) => $s->getId(), $page->all()));
    }

    public function test_受注一覧はmetaからページング情報を組み立てる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        $page = (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertSame(120, $page->getTotal());
        $this->assertSame(2, $page->getLimit());
        $this->assertSame(0, $page->getOffset());
    }

    public function test_受注一覧は正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales', $mock->uri());
    }

    public function test_検索条件をクエリ文字列に変換して送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        (new Sales('my-token', $mock->client()))->page(new SearchParameters([
            'make_date_min' => '2024-01-01',
            'make_date_max' => '2024-01-31 23:59:59',
            'customer_furigana' => 'ヤマダタロウ',
            'accepted_mail_state' => 'sent',
            'limit' => 50,
            'offset' => 100,
            'paid' => true,
        ]));

        $query = $mock->query();
        $this->assertSame('2024-01-01', $query['make_date_min']);
        $this->assertSame('2024-01-31 23:59:59', $query['make_date_max']);
        $this->assertSame('ヤマダタロウ', $query['customer_furigana']);
        $this->assertSame('sent', $query['accepted_mail_state']);
        $this->assertSame('50', $query['limit']);
        $this->assertSame('100', $query['offset']);
        $this->assertSame('1', $query['paid']);
    }

    public function test_検索条件が空ならクエリ文字列を付けない(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertSame([], $mock->query());
    }

    public function test_顧客メールアドレスで部分一致検索できる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_page.json'));

        (new Sales('my-token', $mock->client()))->page(new SearchParameters([
            'customer_mail' => 'customer@example.com',
        ]));

        $this->assertSame(['customer_mail' => 'customer@example.com'], $mock->query());
    }

    public function test_顧客メールアドレス未指定時はクエリに含めない(): void
    {
        $parameters = new SearchParameters([]);

        $this->assertArrayNotHasKey('customer_mail', $parameters->toArrayRecursive());
    }

    public function test_afterとbeforeは検索条件として受け付けない(): void
    {
        $parameters = new SearchParameters([
            'after' => '2024-01-01',
            'before' => '2024-01-31',
        ]);

        $this->assertSame([], $parameters->toArrayRecursive());
    }

    public function test_受注一覧のエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $errors = (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertInstanceOf(Errors::class, $errors);
    }

    public function test_受注一覧の200応答でmetaが欠損しても要素を保持する(): void
    {
        $mock = HttpMock::json(200, '{"sales":[{"id":1001}]}');

        $page = (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame([1001], \array_map(fn($sale) => $sale->getId(), $page->all()));
        $this->expectException(MissingPaginationException::class);
        $this->expectExceptionMessage(
            'GET /v1/sales のレスポンスにページネーション情報「meta」がありません。ページング値を取得できません。',
        );

        $page->getTotal();
    }

    public function test_受注一覧の200応答でmetaが不正なら固有例外で早期に失敗する(): void
    {
        $mock = HttpMock::json(200, '{"sales":[],"meta":{"total":"0","limit":10,"offset":0}}');

        $this->expectException(InvalidPaginationException::class);
        $this->expectExceptionMessage(
            'GET /v1/sales のレスポンスのページネーション情報「meta.total」が不正です。int を期待しましたが string でした。',
        );

        (new Sales('my-token', $mock->client()))->page(new SearchParameters([]));
    }

    // --- one --------------------------------------------------------------

    public function test_受注を1件取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $sale = (new Sales('my-token', $mock->client()))->one(1001);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame(1001, $sale->getId());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001', $mock->uri());
    }

    public function test_受注fixtureで欠損する決済方法IDは参照時に固有例外になる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));
        $sale = (new Sales('my-token', $mock->client()))->one(1001);

        $this->expectException(MissingFieldException::class);

        $sale->getPaymentId();
    }

    public function test_受注IDは文字列でも渡せる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Sales('my-token', $mock->client()))->one('1001');

        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001', $mock->uri());
    }

    public function test_受注1件取得のエラーレスポンス(): void
    {
        $mock = HttpMock::json(404, '{"errors":[{"code":"404100","message":"NG","status":404}]}');

        $errors = (new Sales('my-token', $mock->client()))->one(9999);

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame('404100', $errors[0]->getCode());
    }

    // --- stat -------------------------------------------------------------

    public function test_売上集計を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_stat.json'));

        $stat = (new Sales('my-token', $mock->client()))->stat(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertSame(12000, $stat->getAmountToday());
        $this->assertSame(3, $stat->getCountToday());
    }

    public function test_売上集計は日付をY_m_d形式に整形する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_stat.json'));

        (new Sales('my-token', $mock->client()))->stat(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->assertStringContainsString('make_date=2024-01-01', $mock->uri());
    }

    public function test_売上集計は日付をmake_dateとして送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sales_stat.json'));

        (new Sales('my-token', $mock->client()))->stat(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->assertSame('https://api.shop-pro.jp/v1/sales/stat?make_date=2024-01-01', $mock->uri());
        $this->assertSame(['make_date' => '2024-01-01'], $mock->query());
    }

    public function test_sales_statキーがない成功応答は参照時に固有例外になる(): void
    {
        $mock = HttpMock::json(200, '{}');
        $stat = (new Sales('my-token', $mock->client()))
            ->stat(new \DateTimeImmutable('2024-01-01 12:00:00'));

        $this->assertInstanceOf(Stat::class, $stat);
        $this->expectException(MissingFieldException::class);

        $stat->getAmountToday();
    }

    // --- create -----------------------------------------------------------

    public function test_受注を作成する(): void
    {
        $mock = HttpMock::json(201, self::fixture('sale.json'));

        $sale = (new Sales('my-token', $mock->client()))->create(
            new SaleCreateInput(self::createFields()),
        );

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame(1001, $sale->getId());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales', $mock->uri());
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
        $this->assertSame(['sale' => self::createFields()], $mock->jsonBody());
    }

    public function test_在庫引当指定は未指定ならクエリなしで真偽値は1と0で送信する(): void
    {
        $mock = new HttpMock([
            new \GuzzleHttp\Psr7\Response(201, [], self::fixture('sale.json')),
            new \GuzzleHttp\Psr7\Response(201, [], self::fixture('sale.json')),
            new \GuzzleHttp\Psr7\Response(201, [], self::fixture('sale.json')),
        ]);
        $sales = new Sales('my-token', $mock->client());
        $input = new SaleCreateInput(self::createFields());

        $sales->create($input);
        $sales->create($input, true);
        $sales->create($input, false);

        $this->assertSame([], $mock->query(0));
        $this->assertSame(['reserve_stocks' => '1'], $mock->query(1));
        $this->assertSame(['reserve_stocks' => '0'], $mock->query(2));
    }

    public function test_既存顧客とゲスト顧客をJSONオブジェクトとして送信する(): void
    {
        $mock = new HttpMock([
            new \GuzzleHttp\Psr7\Response(201, [], self::fixture('sale.json')),
            new \GuzzleHttp\Psr7\Response(201, [], self::fixture('sale.json')),
            new \GuzzleHttp\Psr7\Response(201, [], self::fixture('sale.json')),
        ]);
        $sales = new Sales('my-token', $mock->client());

        $sales->create(new SaleCreateInput(self::createFields(['customer' => ['id' => 501]])));
        $sales->create(new SaleCreateInput(self::createFields(['customer' => [
            'name' => 'ゲスト',
            'mail' => 'guest@example.com',
        ]])));
        $sales->create(new SaleCreateInput(self::createFields(['customer' => []])));

        $this->assertSame(['id' => 501], $mock->jsonBody(0)['sale']['customer']);
        $this->assertSame(
            ['name' => 'ゲスト', 'mail' => 'guest@example.com'],
            $mock->jsonBody(1)['sale']['customer'],
        );
        $this->assertSame('{"sale":{"customer":{},"details":[{"product_id":101,"product_num":2}],"payment_id":3}}', $mock->body(2));
    }

    public function test_プラン制限の401はErrorsを返す(): void
    {
        $mock = HttpMock::json(401, '{"errors":[{"code":"401200","message":"現在契約中のプランではご利用いただけません。","status":401}]}');

        $errors = (new Sales('my-token', $mock->client()))->create(
            new SaleCreateInput(self::createFields()),
        );

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame('401200', $errors[0]->getCode());
    }

    public function test_受注作成のトップレベル必須項目を送信前に検証する(): void
    {
        $mock = HttpMock::json(201, self::fixture('sale.json'));

        try {
            (new Sales('my-token', $mock->client()))->create(new SaleCreateInput([]));
            $this->fail(ParameterException::class . ' が投げられませんでした。');
        } catch (ParameterException $exception) {
            $this->assertSame(
                '受注データの作成には payment_id, details を指定してください (未指定: payment_id, details)。',
                $exception->getMessage(),
            );
            $this->assertSame(0, $mock->countRequests());
        }
    }

    public function test_受注作成のトップレベル必須項目が一方でも欠ければ拒否する(): void
    {
        foreach (['payment_id', 'details'] as $missing) {
            $mock = HttpMock::json(201, self::fixture('sale.json'));
            $fields = self::createFields();
            unset($fields[$missing]);

            try {
                (new Sales('my-token', $mock->client()))->create(new SaleCreateInput($fields));
                $this->fail($missing . ' の欠落が拒否されませんでした。');
            } catch (ParameterException $exception) {
                $this->assertStringContainsString('未指定: ' . $missing, $exception->getMessage());
                $this->assertSame(0, $mock->countRequests());
            }
        }
    }

    public function test_受注作成の明細が空配列なら送信前に拒否する(): void
    {
        $mock = HttpMock::json(201, self::fixture('sale.json'));

        try {
            (new Sales('my-token', $mock->client()))->create(new SaleCreateInput(self::createFields([
                'details' => [],
            ])));
            $this->fail('details の空配列が拒否されませんでした。');
        } catch (ParameterException $exception) {
            $this->assertSame(
                '受注データの作成には details を1件以上指定してください (指定件数: 0)。',
                $exception->getMessage(),
            );
            $this->assertSame(0, $mock->countRequests());
        }
    }

    public function test_受注明細の各要素の必須項目を送信前に検証する(): void
    {
        foreach (['product_id', 'product_num'] as $missing) {
            $mock = HttpMock::json(201, self::fixture('sale.json'));
            $detail = ['product_id' => 101, 'product_num' => 1];
            unset($detail[$missing]);

            try {
                (new Sales('my-token', $mock->client()))->create(new SaleCreateInput(self::createFields([
                    'details' => [$detail],
                ])));
                $this->fail($missing . ' の欠落が拒否されませんでした。');
            } catch (ParameterException $exception) {
                $this->assertSame(
                    '受注明細 details[0] には product_id, product_num を指定してください (未指定: '
                    . $missing . ')。',
                    $exception->getMessage(),
                );
                $this->assertSame(0, $mock->countRequests());
            }
        }
    }

    public function test_お届け先の各要素の必須項目を送信前に検証する(): void
    {
        $required = [
            'delivery_id' => 1,
            'name' => '配送先',
            'furigana' => 'ハイソウサキ',
            'postal' => '1508512',
            'pref_id' => 13,
            'address1' => '渋谷区桜丘町26-1',
            'tel' => '03-5456-2622',
        ];

        foreach (\array_keys($required) as $missing) {
            $mock = HttpMock::json(201, self::fixture('sale.json'));
            $delivery = $required;
            unset($delivery[$missing]);

            try {
                (new Sales('my-token', $mock->client()))->create(new SaleCreateInput(self::createFields([
                    'sale_deliveries' => [$delivery],
                ])));
                $this->fail($missing . ' の欠落が拒否されませんでした。');
            } catch (ParameterException $exception) {
                $this->assertSame(
                    'お届け先 sale_deliveries[0] には delivery_id, name, furigana, postal, pref_id, address1, tel'
                    . ' を指定してください (未指定: ' . $missing . ')。',
                    $exception->getMessage(),
                );
                $this->assertSame(0, $mock->countRequests());
            }
        }
    }

    public function test_お届け先は省略できる(): void
    {
        $mock = HttpMock::json(201, self::fixture('sale.json'));

        (new Sales('my-token', $mock->client()))->create(
            new SaleCreateInput(self::createFields()),
        );

        $this->assertArrayNotHasKey('sale_deliveries', $mock->jsonBody()['sale']);
    }

    // --- update -----------------------------------------------------------

    public function test_受注を更新する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $updater = new SaleUpdateInput(['paid' => true, 'point_state' => 'fixed']);
        $sale = (new Sales('my-token', $mock->client()))->update('external-1001', $updater);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/external-1001', $mock->uri());
    }

    public function test_受注更新はsaleキーでJSONボディを送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $updater = new SaleUpdateInput(['paid' => true, 'point_state' => 'fixed']);
        (new Sales('my-token', $mock->client()))->update(1001, $updater);

        $this->assertSame(
            ['sale' => ['paid' => true, 'point_state' => 'fixed']],
            $mock->jsonBody(),
        );
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
    }

    public function test_受注更新は空の入力をJSONオブジェクトとして送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Sales('my-token', $mock->client()))->update(1001, new SaleUpdateInput([]));

        $this->assertSame('{"sale":{}}', $mock->body());
    }

    public function test_受注更新は指定した項目だけを送信できる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $updater = new SaleUpdateInput([]);
        $updater->setPaid(true);
        (new Sales('my-token', $mock->client()))->update(1001, $updater);

        $this->assertSame(
            ['sale' => ['paid' => true]],
            $mock->jsonBody(),
        );
    }

    public function test_受注更新は入力の未宣言IDをJSONボディに含めない(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));
        $input = new SaleUpdateInput(['id' => 1001, 'paid' => true]);

        (new Sales('my-token', $mock->client()))->update(1001, $input);

        $this->assertSame(['sale' => ['paid' => true]], $mock->jsonBody());
    }

    public function test_受注更新のエラーレスポンス(): void
    {
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors = (new Sales('my-token', $mock->client()))
            ->update(1001, new SaleUpdateInput(['paid' => true, 'point_state' => PointState::FIXED->value]));

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(2, $errors->count());
    }

    // --- cancel -----------------------------------------------------------

    public function test_受注をキャンセルする(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $sale = (new Sales('my-token', $mock->client()))->cancel(1001);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001/cancel', $mock->uri());
    }

    public function test_キャンセルは既定で在庫を戻さない(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Sales('my-token', $mock->client()))->cancel(1001);

        $this->assertSame(['restock' => false], $mock->jsonBody());
    }

    public function test_キャンセル時に在庫を戻せる(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Sales('my-token', $mock->client()))->cancel(1001, true);

        $this->assertSame(['restock' => true], $mock->jsonBody());
    }

    // --- sendMail ---------------------------------------------------------

    public function test_メールを送信するとtrueを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $result = (new Sales('my-token', $mock->client()))->sendMail(1001, MailType::ACCEPTED);

        $this->assertTrue($result);
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001/mails', $mock->uri());
    }

    public function test_メール送信はmailキーで種別を送信する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Sales('my-token', $mock->client()))->sendMail(1001, MailType::DELIVERED);

        $this->assertSame(['mail' => ['type' => 'delivered']], $mock->jsonBody());
    }

    public function test_メール送信のエラーレスポンス(): void
    {
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors = (new Sales('my-token', $mock->client()))->sendMail(1001, MailType::PAID);

        $this->assertInstanceOf(Errors::class, $errors);
    }

    // --- アクセストークン ---------------------------------------------------

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        (new Sales('my-token', $mock->client()))->one(1001, 'override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }
}
