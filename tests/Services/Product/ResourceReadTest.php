<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Services\Product\Category as ProductCategory;
use Shimoning\ColorMeShopApi\Services\Product\Group as ProductGroup;
use Shimoning\ColorMeShopApi\Services\Product\Stock as ProductStock;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Group\Group;
use Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category\SmallCategory;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock as StockEntity;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ResourceReadTest extends TestCase
{
    // --- stocks -----------------------------------------------------------

    public function test_在庫一覧は検索クエリとmetaを持つPageを返す(): void
    {
        $mock = HttpMock::json(200, self::fixture('stocks_page.json'));

        $page = (new ProductStock('token', $mock->client()))->page(new StockSearchParameters([
            'ids' => [101, 102],
            'display_state' => 'showing',
            'recent_zero_stocks' => true,
            'limit' => 50,
            'offset' => 10,
        ]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertCount(2, $page);
        $this->assertContainsOnlyInstancesOf(StockEntity::class, $page->all());
        $this->assertSame(2, $page->getTotal());
        $this->assertSame(10, $page->getLimit());
        $this->assertSame(0, $page->getOffset());
        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame(
            'https://api.shop-pro.jp/v1/stocks',
            (string) $mock->request()->getUri()->withQuery(''),
        );
        $this->assertSame([
            'ids' => '101,102',
            'display_state' => 'showing',
            'recent_zero_stocks' => '1',
            'limit' => '50',
            'offset' => '10',
        ], $mock->query());
    }

    public function test_在庫一覧のエラー応答はErrorsになる(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $result = (new ProductStock('token', $mock->client()))->page(new StockSearchParameters([]));

        $this->assertInstanceOf(Errors::class, $result);
    }

    public function test_在庫一覧の200応答でmetaが欠損しても要素を保持する(): void
    {
        $response = self::fixtureArray('stocks_page.json');
        unset($response['meta']);
        $mock = HttpMock::json(200, \json_encode($response, \JSON_THROW_ON_ERROR));

        $page = (new ProductStock('token', $mock->client()))->page(new StockSearchParameters([]));
        $getters = [
            'getTotal' => static fn(Page $page): int => $page->getTotal(),
            'getLimit' => static fn(Page $page): int => $page->getLimit(),
            'getOffset' => static fn(Page $page): int => $page->getOffset(),
        ];

        $this->assertInstanceOf(Page::class, $page);
        $this->assertCount(2, $page);
        $this->assertContainsOnlyInstancesOf(StockEntity::class, $page->all());

        foreach ($getters as $method => $getter) {
            $actualException = null;
            try {
                $getter($page);
            } catch (MissingPaginationException $exception) {
                $actualException = $exception;
            }

            $this->assertInstanceOf(MissingPaginationException::class, $actualException, $method);
            $this->assertSame(
                'GET /v1/stocks のレスポンスにページネーション情報「meta」がありません。ページング値を取得できません。',
                $actualException->getMessage(),
                $method,
            );
        }
    }

    public function test_stocks引数のアクセストークンが空なら送信前に例外になる(): void
    {
        $this->expectException(ParameterException::class);
        (new ProductStock('constructor-token'))->page(new StockSearchParameters([]), '');
    }

    public function test_stocks引数のアクセストークンを優先する(): void
    {
        $mock = HttpMock::json(200, '{"stocks":[],"meta":{"total":0,"limit":10,"offset":0}}');

        (new ProductStock('constructor-token', $mock->client()))->page(
            new StockSearchParameters([]),
            'argument-token',
        );

        $this->assertSame('Bearer argument-token', $mock->header('Authorization'));
    }

    // --- groups -----------------------------------------------------------

    public function test_商品グループの一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('groups.json'));

        $groups = (new ProductGroup('my-token', $mock->client()))->all();

        $this->assertInstanceOf(Collection::class, $groups);
        $this->assertSame(2, $groups->count());
        $this->assertContainsOnlyInstancesOf(Group::class, $groups->all());
        $this->assertSame('新着商品', $groups[0]->getName());
        $this->assertSame(GroupDisplayState::SHOWING, $groups[0]->getDisplayState());
    }

    /**
     * 実 API は members_only のグループを返す (2026-09-21 観測)。一覧に1件でも含まれると
     * 一覧全体が読めなくなっていた不具合の回帰テスト。
     */
    public function test_会員限定のグループを含む一覧を取得できる(): void
    {
        $mock = HttpMock::json(200, '{"groups":['
            . '{"id":1,"account_id":"my-shop","name":"会員限定","display_state":"members_only","parent_group_id":null},'
            . '{"id":2,"account_id":"my-shop","name":"新着商品","display_state":"showing","parent_group_id":null}'
            . ']}');

        $groups = (new ProductGroup('my-token', $mock->client()))->all();

        $this->assertInstanceOf(Collection::class, $groups);
        $this->assertSame(GroupDisplayState::MEMBER_ONLY, $groups[0]->getDisplayState());
        $this->assertSame(GroupDisplayState::SHOWING, $groups[1]->getDisplayState());
    }

    public function test_会員限定のグループを単体で取得できる(): void
    {
        $mock = HttpMock::json(200, '{"group":{"id":1,"account_id":"my-shop","name":"会員限定","display_state":"members_only","parent_group_id":null}}');

        $group = (new ProductGroup('my-token', $mock->client()))->one(1);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame(GroupDisplayState::MEMBER_ONLY, $group->getDisplayState());
    }

    public function test_商品グループは正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('groups.json'));

        (new ProductGroup('my-token', $mock->client()))->all();

        $this->assertSame('https://api.shop-pro.jp/v1/groups', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_groupsキーがなければ空のコレクションを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $this->assertSame(0, (new ProductGroup('my-token', $mock->client()))->all()->count());
    }

    public function test_商品グループのエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new ProductGroup('my-token', $mock->client()))->all());
    }

    // --- categories -------------------------------------------------------

    public function test_商品カテゴリーの一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('categories.json'));

        $categories = (new ProductCategory('my-token', $mock->client()))->all();

        $this->assertSame(1, $categories->count());
        $this->assertContainsOnlyInstancesOf(BigCategory::class, $categories->all());
        $this->assertSame('トップス', $categories[0]->getName());
    }

    public function test_子カテゴリーにはchildrenのgetterがない(): void
    {
        $mock = HttpMock::json(200, '{"categories":[{"id_small":0,"children":[{"id_big":1,"id_small":1}]}]}');
        $categories = (new ProductCategory('my-token', $mock->client()))->all();
        $child = $categories[0]->getChildren()[0];

        $this->assertInstanceOf(SmallCategory::class, $child);
        $this->assertFalse(\method_exists($child, 'getChildren'));
    }

    public function test_トップレベルの小カテゴリーもfactoryで変換する(): void
    {
        $mock = HttpMock::json(200, '{"categories":[{"id_small":1}]}');

        $categories = (new ProductCategory('my-token', $mock->client()))->all();

        $this->assertSame(1, $categories->count());
        $this->assertInstanceOf(SmallCategory::class, $categories[0]);
    }

    public function test_トップレベルのid_smallが不正なら固有例外になる(): void
    {
        $mock = HttpMock::json(200, '{"categories":[{"id_small":"0"}]}');

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('id_small');
        (new ProductCategory('my-token', $mock->client()))->all();
    }

    #[DataProvider('invalidTopLevelCategoryProvider')]
    public function test_トップレベルの非配列カテゴリーは位置付き固有例外になる(
        string $body,
        string $field,
    ): void {
        $mock = HttpMock::json(200, $body);

        try {
            (new ProductCategory('my-token', $mock->client()))->all();
            $this->fail('不正なカテゴリー要素が受理されました。');
        } catch (InvalidFieldException $exception) {
            $this->assertStringContainsString($field, $exception->getMessage());
            $this->assertStringContainsString('配列要素を', $exception->getMessage());
            $this->assertInstanceOf(\TypeError::class, $exception->getPrevious());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function invalidTopLevelCategoryProvider(): array
    {
        return [
            'null at 0' => ['{"categories":[null]}', 'categories[0]'],
            'string at 1' => ['{"categories":[{"id_small":1},"x"]}', 'categories[1]'],
            'number at 2' => ['{"categories":[{"id_small":1},{"id_small":2},42]}', 'categories[2]'],
        ];
    }

    public function test_categoriesが配列以外なら従来の引数例外を維持する(): void
    {
        $mock = HttpMock::json(200, '{"categories":"invalid"}');

        $this->expectException(ParameterException::class);
        (new ProductCategory('my-token', $mock->client()))->all();
    }

    public function test_商品カテゴリーは正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('categories.json'));

        (new ProductCategory('my-token', $mock->client()))->all();

        $this->assertSame('https://api.shop-pro.jp/v1/categories', $mock->uri());
    }

    public function test_categoriesキーがなければ空のコレクションを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $this->assertSame(0, (new ProductCategory('my-token', $mock->client()))->all()->count());
    }

    public function test_商品カテゴリーのエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new ProductCategory('my-token', $mock->client()))->all());
    }
}
