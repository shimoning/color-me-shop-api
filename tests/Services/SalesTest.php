<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Sales;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class SalesTest extends TestCase
{
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

    // --- update -----------------------------------------------------------

    public function test_受注を更新する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $updater = new SaleUpdater(['id' => 1001, 'paid' => true, 'point_state' => 'fixed']);
        $sale = (new Sales('my-token', $mock->client()))->update($updater);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1001', $mock->uri());
    }

    public function test_受注更新はsaleキーでJSONボディを送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('sale.json'));

        $updater = new SaleUpdater(['id' => 1001, 'paid' => true, 'point_state' => 'fixed']);
        (new Sales('my-token', $mock->client()))->update($updater);

        $this->assertSame(
            ['sale' => ['id' => 1001, 'paid' => true, 'point_state' => 'fixed']],
            $mock->jsonBody(),
        );
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
    }

    public function test_受注更新のエラーレスポンス(): void
    {
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors = (new Sales('my-token', $mock->client()))
            ->update(new SaleUpdater(['id' => 1001, 'paid' => true, 'point_state' => PointState::FIXED->value]));

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
