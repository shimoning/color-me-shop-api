<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sale\Sale;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\DetailCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\Stat\Stat;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 受注 API を操作するサービス。
 */
class Sales extends Service
{
    /**
     * 受注データのリストを取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSales
     * @param SearchParameters $searchParameters
     * @param string|null $accessToken
     * @return Page<Sale>|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/sales'),
            $searchParameters->toArrayRecursive(),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Page => Page::build(
                Sale::class,
                $data,
                'sales',
                'meta',
                'GET /v1/sales のレスポンス',
            ),
        );
    }

    /**
     * 受注データの取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
     * @param int|string $id
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function one(int|string $id, ?string $accessToken = null): Sale|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/sales/' . $id),
        );

        return $this->_handle($response, fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * 売上集計の取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param \DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @return Stat|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function stat(
        \DateTimeInterface $dateTime,
        ?string $accessToken = null,
    ): Stat|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/sales/stat'),
            [
                'make_date' => $dateTime->format('Y-m-d'),
            ],
        );

        return $this->_handle($response, fn(?array $data): Stat => new Stat($data['sales_stat'] ?? []));
    }

    /**
     * 受注データの作成
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * プレミアムプラン限定。対象外のプランでは 401 (code 401200) の Errors を返す (2026-10-01)。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
     * @param SaleCreateInput $input
     * @param bool|null $reserveStocks 在庫を引き当てるか。null の場合はクエリへ含めない
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws ParameterException 実効アクセストークンが空文字、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-sale-create-observation.md
     */
    public function create(
        SaleCreateInput $input,
        ?bool $reserveStocks = null,
        ?string $accessToken = null,
    ): Sale|Errors {
        $request = $this->_request(['json' => true], $accessToken);
        $fields = self::requireCreateFields($input);
        if (isset($fields['customer']) && \is_array($fields['customer'])) {
            $fields['customer'] = self::_jsonObject($fields['customer']);
        }

        $endpoint = $this->_endpoint('/sales');
        if ($reserveStocks !== null) {
            $endpoint .= '?' . \http_build_query(['reserve_stocks' => $reserveStocks]);
        }

        $response = $request->post(
            $endpoint,
            ['sale' => self::_jsonObject($fields)],
        );

        return $this->_handle($response, static fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * 受注データの更新
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
     * @param int|string $id
     * @param SaleUpdateInput $input
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(
        int|string $id,
        SaleUpdateInput $input,
        ?string $accessToken = null,
    ): Sale|Errors {
        $response = $this->_request([
            'json' => true,
        ], $accessToken)->put(
            $this->_endpoint('/sales/' . $id),
            [
                'sale' => self::_jsonObject($input->toArrayRecursive()),
            ],
        );

        return $this->_handle($response, fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * 受注作成入力と各子要素の必須フィールドを確認する。
     *
     * @return array<string, mixed>
     * @throws ParameterException 必須フィールドが未指定の場合
     */
    private static function requireCreateFields(SaleCreateInput $input): array
    {
        $fields = $input->toArrayRecursive();
        $missing = \array_diff(SaleCreateInput::REQUIRED_FIELDS, \array_keys($fields));
        if ($missing !== []) {
            throw new ParameterException(\sprintf(
                '受注データの作成には %s を指定してください (未指定: %s)。',
                \implode(', ', SaleCreateInput::REQUIRED_FIELDS),
                \implode(', ', $missing),
            ));
        }
        if ($fields['details'] === []) {
            throw new ParameterException(
                '受注データの作成には details を1件以上指定してください (指定件数: 0)。',
            );
        }

        foreach ($fields['details'] as $index => $detail) {
            $missing = \array_diff(DetailCreateInput::REQUIRED_FIELDS, \array_keys($detail));
            if ($missing !== []) {
                throw new ParameterException(\sprintf(
                    '受注明細 details[%d] には %s を指定してください (未指定: %s)。',
                    $index,
                    \implode(', ', DetailCreateInput::REQUIRED_FIELDS),
                    \implode(', ', $missing),
                ));
            }
        }

        foreach ($fields['sale_deliveries'] ?? [] as $index => $delivery) {
            $missing = \array_diff(DeliveryCreateInput::REQUIRED_FIELDS, \array_keys($delivery));
            if ($missing !== []) {
                throw new ParameterException(\sprintf(
                    'お届け先 sale_deliveries[%d] には %s を指定してください (未指定: %s)。',
                    $index,
                    \implode(', ', DeliveryCreateInput::REQUIRED_FIELDS),
                    \implode(', ', $missing),
                ));
            }
        }

        return $fields;
    }

    /**
     * 受注のキャンセル
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/cancelSale
     * @param int|string $id
     * @param bool|null $restock
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function cancel(
        int|string $id,
        ?bool $restock = false,
        ?string $accessToken = null,
    ): Sale|Errors {
        $response = $this->_request([
            'json' => true,
        ], $accessToken)->put(
            $this->_endpoint('/sales/' . $id . '/cancel'),
            [
                'restock' => $restock,
            ],
        );

        return $this->_handle($response, fn(?array $data): Sale => new Sale($data['sale'] ?? []));
    }

    /**
     * メールの送信
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/sendSalesMail
     * @param int|string $id
     * @param MailType $mailType
     * @param string|null $accessToken
     * @return true|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function sendMail(
        int|string $id,
        MailType $mailType,
        ?string $accessToken = null,
    ): bool|Errors {
        $response = $this->_request([
            'json' => true,
        ], $accessToken)->post(
            $this->_endpoint('/sales/' . $id . '/mails'),
            [
                'mail' => [
                    'type' => $mailType->value,
                ],
            ],
        );

        return $this->_handle($response, fn(?array $_data): bool => true);
    }
}
