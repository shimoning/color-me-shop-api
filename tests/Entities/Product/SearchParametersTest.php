<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Constants\SortDirection;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;
use Shimoning\ColorMeShopApi\Values\Sort as BaseSort;
use Shimoning\ColorMeShopApi\Values\Product\Sort;

class SearchParametersTest extends TestCase
{
    public function test_全25条件をOpenAPIのクエリ形式に変換する(): void
    {
        $input = [
            'ids' => [101, 102], 'category_id_big' => 1, 'category_id_small' => 2,
            'group_ids' => [301, 302], 'model_number' => 'TEST', 'name' => '商品',
            'display_state' => 'showing', 'stocks' => 3, 'stock_managed' => true,
            'recent_zero_stocks' => false, 'make_date_min' => '2024-01-01',
            'make_date_max' => '2024-12-31', 'update_date_min' => '2024-01-01',
            'update_date_max' => '2024-12-31', 'sales_price_min' => 1,
            'sales_price_max' => 2, 'price_min' => 3, 'price_max' => 4,
            'members_price_min' => 5, 'members_price_max' => 6, 'jan_code' => '123',
            'sort' => ['-make_date'], 'fields' => ['id', 'name'], 'limit' => 50, 'offset' => 10,
        ];
        $parameters = new SearchParameters($input);

        $this->assertInstanceOf(RequestEntity::class, $parameters);
        $actual = $parameters->toArrayRecursive();
        $this->assertCount(25, $actual);
        $this->assertSame('101,102', $actual['ids']);
        $this->assertSame('301,302', $actual['group_ids']);
        $this->assertSame(ProductDisplayState::SHOWING, $parameters->getDisplayState());
        $this->assertSame('2024-01-01', $actual['make_date_min']);
        $this->assertSame('id,name', $actual['fields']);
        $this->assertSame(['id', 'name'], $parameters->toArray()['fields']);
        $this->assertSame(50, $actual['limit']);
        $this->assertSame('-make_date', $actual['sort']);
        $this->assertEquals([new Sort('-make_date')], $parameters->toArray()['sort']);
    }

    public function test_sortの配列をカンマ区切りで送る(): void
    {
        $parameters = new SearchParameters(['sort' => ['-sales_price', 'make_date']]);

        $sorts = $parameters->toArray()['sort'];
        $this->assertContainsOnlyInstancesOf(Sort::class, $sorts);
        $this->assertSame('sales_price', $sorts[0]->getField());
        $this->assertSame(SortDirection::DESC, $sorts[0]->getDirection());
        $this->assertSame('make_date', $sorts[1]->getField());
        $this->assertSame(SortDirection::ASC, $sorts[1]->getDirection());
        $this->assertSame(['sort' => '-sales_price,make_date'], $parameters->toArrayRecursive());
    }

    public function test_sortは生の値と値オブジェクトを混在できる(): void
    {
        $declared = new Sort('-sales_price');
        $parent = new BaseSort('make_date');
        $parameters = new SearchParameters([
            'sort' => [$declared, 'price', $parent],
        ]);

        $sorts = $parameters->toArray()['sort'];
        $this->assertSame($declared, $sorts[0]);
        $this->assertSame('price', $sorts[1]->get());
        $this->assertNotSame($parent, $sorts[2]);
        $this->assertContainsOnlyInstancesOf(Sort::class, $sorts);
        $this->assertSame(
            ['sort' => '-sales_price,price,make_date'],
            $parameters->toArrayRecursive(),
        );
    }

    public function test_親Sortの値を商品Sortの制約で再検証する(): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['sort' => [new BaseSort('name')]]);
    }

    public function test_sortは商品一覧で使用できる5列を受け付ける(): void
    {
        $parameters = new SearchParameters([
            'sort' => ['make_date', '-update_date', 'sales_price', '-price', 'members_price'],
        ]);

        $sorts = $parameters->toArray()['sort'];
        $this->assertContainsOnlyInstancesOf(Sort::class, $sorts);
        $this->assertSame(
            ['sort' => 'make_date,-update_date,sales_price,-price,members_price'],
            $parameters->toArrayRecursive(),
        );
    }

    public function test_空のsort配列はクエリから除外する(): void
    {
        $parameters = new SearchParameters(['sort' => []]);

        $this->assertSame([], $parameters->toArray()['sort']);
        $this->assertSame([], $parameters->toArrayRecursive());
    }

    public function test_sortのnullは他フィールド同様に扱う(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
        $this->assertSame(['sort' => null], (new SearchParameters(['sort' => null]))->toArrayRecursive());
    }

    /** @dataProvider invalidSortProvider */
    public function test_不正なsortを拒否する(mixed $sort): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['sort' => $sort]);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidSortProvider(): array
    {
        return [
            '文字列' => ['-make_date'],
            '配列でないSort' => [new Sort('make_date')],
            '文字列でない要素' => [['make_date', 1]],
            '空文字' => [['']],
            'ハイフン2つ' => [['--make_date']],
            '空白' => [['make date']],
            'カンマ' => [['make_date,sales_price']],
            '仕様にない列' => [['name']],
            '大文字の列' => [['SALES_PRICE']],
            '空でない連想配列' => [['first' => 'make_date']],
            '整数' => [1],
        ];
    }

    public function test_未指定はクエリに出さない(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
    }

    public function test_配列の不正型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['ids' => [101, 'bad']]);
    }

    public function test_空のfieldsは送らない(): void
    {
        $parameters = new SearchParameters(['fields' => []]);

        $this->assertSame([], $parameters->toArray()['fields']);
        $this->assertSame([], $parameters->toArrayRecursive());
    }

    /** @dataProvider invalidFieldsProvider */
    public function test_不正なfieldsを拒否する(mixed $fields): void
    {
        $this->expectException(InvalidFieldException::class);

        new SearchParameters(['fields' => $fields]);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            '文字列' => ['id,name'],
            '文字列でない要素' => [['id', 1]],
            'リストでない配列' => [['first' => 'id']],
        ];
    }
}
