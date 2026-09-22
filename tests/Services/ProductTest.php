<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductTest extends TestCase
{
    // --- groups -----------------------------------------------------------

    public function test_商品グループの一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('groups.json'));

        $groups = (new Product('my-token', $mock->client()))->groups();

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

        $groups = (new Product('my-token', $mock->client()))->groups();

        $this->assertInstanceOf(Collection::class, $groups);
        $this->assertSame(GroupDisplayState::MEMBER_ONLY, $groups[0]->getDisplayState());
        $this->assertSame(GroupDisplayState::SHOWING, $groups[1]->getDisplayState());
    }

    public function test_会員限定のグループを単体で取得できる(): void
    {
        $mock = HttpMock::json(200, '{"group":{"id":1,"account_id":"my-shop","name":"会員限定","display_state":"members_only","parent_group_id":null}}');

        $group = (new Product('my-token', $mock->client()))->group(1);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame(GroupDisplayState::MEMBER_ONLY, $group->getDisplayState());
    }

    public function test_商品グループは正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('groups.json'));

        (new Product('my-token', $mock->client()))->groups();

        $this->assertSame('https://api.shop-pro.jp/v1/groups', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_groupsキーがなければ空のコレクションを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $this->assertSame(0, (new Product('my-token', $mock->client()))->groups()->count());
    }

    public function test_商品グループのエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new Product('my-token', $mock->client()))->groups());
    }

    // --- categories -------------------------------------------------------

    public function test_商品カテゴリーの一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('categories.json'));

        $categories = (new Product('my-token', $mock->client()))->categories();

        $this->assertSame(1, $categories->count());
        $this->assertContainsOnlyInstancesOf(BigCategory::class, $categories->all());
        $this->assertSame('トップス', $categories[0]->getName());
    }

    public function test_子カテゴリーにはchildrenのgetterがない(): void
    {
        $mock = HttpMock::json(200, '{"categories":[{"id_small":0,"children":[{"id_big":1,"id_small":1}]}]}');
        $categories = (new Product('my-token', $mock->client()))->categories();
        $child = $categories[0]->getChildren()[0];

        $this->assertInstanceOf(SmallCategory::class, $child);
        $this->assertFalse(\method_exists($child, 'getChildren'));
    }

    public function test_トップレベルの小カテゴリーもfactoryで変換する(): void
    {
        $mock = HttpMock::json(200, '{"categories":[{"id_small":1}]}');

        $categories = (new Product('my-token', $mock->client()))->categories();

        $this->assertSame(1, $categories->count());
        $this->assertInstanceOf(SmallCategory::class, $categories[0]);
    }

    public function test_トップレベルのid_smallが不正なら固有例外になる(): void
    {
        $mock = HttpMock::json(200, '{"categories":[{"id_small":"0"}]}');

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('id_small');
        (new Product('my-token', $mock->client()))->categories();
    }

    #[DataProvider('invalidTopLevelCategoryProvider')]
    public function test_トップレベルの非配列カテゴリーは位置付き固有例外になる(
        string $body,
        string $field,
    ): void {
        $mock = HttpMock::json(200, $body);

        try {
            (new Product('my-token', $mock->client()))->categories();
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
        (new Product('my-token', $mock->client()))->categories();
    }

    public function test_商品カテゴリーは正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('categories.json'));

        (new Product('my-token', $mock->client()))->categories();

        $this->assertSame('https://api.shop-pro.jp/v1/categories', $mock->uri());
    }

    public function test_categoriesキーがなければ空のコレクションを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $this->assertSame(0, (new Product('my-token', $mock->client()))->categories()->count());
    }

    public function test_商品カテゴリーのエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new Product('my-token', $mock->client()))->categories());
    }
}
