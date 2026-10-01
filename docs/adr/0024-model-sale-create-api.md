# ADR 0024: 受注作成 API の入力を表現する

- 状態: 採用
- 決定日: 2026-10-01

## 文脈

受注作成 API（`POST /v1/sales`、`operationId: createSale`）は、受注 API のうちライブラリが唯一
実装していない操作だった。README の「未実装」の一覧にも載っておらず、対応状況が分かりにくかった。

公式 OpenAPI の定義は次のとおりである。

- body は `sale` の下に `customer`（任意）、`sale_deliveries[]`（配送不要の商品だけの場合を除き必須）、
  `details[]`（必須）、`payment_id`（必須）を持つ。`sale_deliveries[]` は 7 項目、`details[]` は 2 項目が
  必須である
- `customer` は「存在する顧客の受注を作る場合は `id` を、そうでない場合は `id` 以外の各属性を渡す」。
  `id` が会員登録済みの顧客なら、`id` 以外の顧客情報は無視される
- クエリ `reserve_stocks`（boolean、既定 `true`）で在庫の引き当てを止められる
- 応答は `201` で、`sale` は受注の取得と同じ 44 項目である
- プレミアムプランでのみ利用できる。操作に `security` キーがなく、説明文に「write_salesスコープが
  必要です」とある

2026-10-01 に実測した。詳細は[受注作成 API の実測記録](../api-sale-create-observation.md)にある。
出典: `c2e00a1`。

- テスト用ショップの契約プランでは `401`（code 401200）で利用できなかった。必須項目を欠いた 2 種類の
  body で観測し、いずれも body の検証より先にこの `401` が返った。**成功（`201`）は観測していない**
- 認証なしでは `401`（code 401010）で、認証は必要だった。`write_sales` の要否は確かめられなかった

## 判断

- **成功を観測できないまま、公式 OpenAPI に基づいて実装する。** 応答は既存の `Entities\Sales\Sale` で
  表す。プランの制約で成功を観測できなかった商品画像の作成（[ADR 0014](0014-model-product-write-api.md)）と
  同じ扱いとし、PHPDoc と README に「成功時の応答の形は未観測」と明記する。出典: `c2e00a1`、`6f62843`。
- `Client::createSale()` と `Services\Sales::create(SaleCreateInput $input, ?bool $reserveStocks = null,
  ?string $accessToken = null)` を設ける。`$reserveStocks` が `null` のときは `reserve_stocks` を送らず、
  API の既定（引き当てる）に任せる。送るときは既存の検索条件と同じく `1` / `0` で表す。出典: `6f62843`。
- 入力クラスは [ADR 0016](0016-unify-request-input-entity-names.md) の命名に従い、`SaleCreateInput` と、
  入れ子の `SaleCustomerCreateInput` / `SaleDeliveryCreateInput` / `SaleDetailCreateInput` とする。受注
  更新の入力（`SaleUpdateInput` / `SaleDeliveryUpdateInput`）とは、要求スキーマの項目が異なるため共用
  しない。出典: `6f62843`。
- **顧客は 1 クラスで表し、既存の顧客を指定する生成メソッド `SaleCustomerCreateInput::existing()` を
  設ける。** ゲスト購入では通常のコンストラクタに各属性を渡す。`id` と他の属性を同時に指定しても例外に
  しない。API が許す組み合わせであり、無視される旨を PHPDoc に書く。出典: `6f62843`。
- 必須項目は、顧客作成（[ADR 0015](0015-model-customer-write-api.md)）と同じく送信前に確かめ、欠けて
  いれば `ParameterException` にする。対象は `payment_id`・`details` と、`details[]` /
  `sale_deliveries[]` の各要素の必須項目である。`sale_deliveries` 自体は商品によって必須かどうかが
  変わるため、ライブラリでは必須にしない。出典: `6f62843`。
- `details` は空配列も拒否する。公式 OpenAPI に件数の下限はないが、明細のない受注は成り立たない。
  出典: `a63d14f`。
- 顧客の `sex` は、公式の定義どおり `male` / `female` だけを受け付ける。出典: `6f62843`。
- フリガナ（`customer.furigana`、`sale_deliveries[].furigana`）には、顧客 API と同じ既存の
  `Values\Furigana` を使う。公式 OpenAPI のパターンは `ヷヸヹヺ` を許すが、`Furigana` はこれを拒否する。
  `Furigana` の制限の根拠は顧客 API の実測（`422`）であり、**受注作成では確かめていない**。同じショップの
  フリガナの検証は顧客 API と同じだろうと仮定し、ライブラリ内でフリガナの扱いを揃えることを優先した。
  受注作成で `ヷヸヹヺ` が受理されると分かった場合は見直す。出典: `6f62843`。
- PHPDoc の必要なスコープは、説明文に基づいて `write_sales` とする。要否は未検証であることを
  [OAuth スコープの突合記録](../auth-scope-audit.md)に記す。出典: `c2e00a1`、`d97addd`。
- **基底 `Entity` は、`FIELD_TYPES` の `entity` で宣言した入れ子に、宣言したクラス（またはそのサブ
  クラス）の組み立て済みインスタンスを受け付ける。** `existing()` の戻り値をそのまま
  `'customer' => SaleCustomerCreateInput::existing($id)` と渡せるようにするためである。単体と配列の
  両方に対応し、インスタンスは複製せず参照を保持する。既存のすべての入力クラスにも効く。
  出典: `dea63aa`。
- ただし**親が要求文脈にあり、渡されたインスタンスが `RequestEntity` でない場合は、その生データから
  要求文脈で組み立て直す。** 応答側として組み立てたインスタンスには、応答用のフォールバック（未知 enum の
  番兵、`FallbackValue` の生文字列）が入りうるため、そのまま受け付けると要求側の厳格な検証をすり抜ける。
  `RequestEntity` のインスタンスは構築時に厳格に検証されるため、そのまま保持する。出典: `f6bffec`。

## 代替案と却下理由

- **成功を観測できるまで実装を見送る案**は、根拠のない応答の形を持ち込まずに済む。しかし受注 API の
  うち作成だけが使えない状態が続く。応答の形は受注の取得と同じで、既存の `Sale` で表せる。ADR 0014 の
  前例に従い、未観測と明記して実装する。
- **既存顧客用とゲスト用で入力クラスを 2 つに分ける案**は、型で区別できる。しかしクラスと
  `SaleCreateInput` 側の分岐が増える。API は 1 つのオブジェクトで両方を表しているため、利用者と協議の
  うえ採用しない。
- **`id` と他の属性を同時に指定したら例外にする案**は、無視される値を黙って送らずに済む。しかし API が
  許す組み合わせをライブラリが禁じることになる。利用者と協議のうえ採用しない。
- **`sale_deliveries` を必須にする案**は、送り忘れを防げる。しかし配送不要の商品だけの受注を作れなく
  なる。どの商品が配送不要かはライブラリでは判断できない。
- **`$reserveStocks` を `bool $reserveStocks = true` にし、常に送る案**は、引数の意味が明確になる。しかし
  API の既定が変わった場合に追従できず、既定を使いたい利用者にも値を送ることになる。
- **入れ子の入力に setter（`setCustomer()` など）を足す案**は、基底 `Entity` を変えずに済む。しかし
  顧客作成の入力（`CustomerCreateInput`）は setter を持たず、入力の組み立て方がコンストラクタ配列と
  setter に分かれる。利用者と協議のうえ採用しない。
- **組み立て済みのインスタンスを複製して保持する案**は、親の外での後からの書き換えが親に反映され
  ない。しかし複製の深さや独自の `__clone` という新しい意味を持ち込む。「渡したインスタンスをそのまま
  使う」と単純に約束するほうが分かりやすいため採用しない。

## 帰結

受注 API のすべての操作（一覧・取得・集計・作成・更新・キャンセル・メール送信）が使えるようになる。
README の「未実装」はショップクーポンだけになる。

プレミアムプラン以外では、受注作成は `401`（code 401200）の `Errors` を返す。成功時の応答の形、
`write_sales` の要否、必須項目の検証（`422`）の内容、`reserve_stocks` の効果は未観測である。利用できる
プランで実測できた時点で、観測記録とこの ADR の関連箇所を更新する。

基底 `Entity` の変更により、既存の入力クラスでも入れ子に組み立て済みのインスタンスを渡せるように
なる。これまで例外になっていた書き方が通るようになる変更であり、既存の呼び出しは壊れない。渡した
インスタンスを後から書き換えると、親にも反映される。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0015: 顧客書き込み API の入力を作成と更新で分ける](0015-model-customer-write-api.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [受注作成 API の実測記録](../api-sale-create-observation.md)（出典コミット: `c2e00a1`）
- [OAuth スコープと公式 OpenAPI の突合記録](../auth-scope-audit.md)
- 受注作成の実装とテストの出典コミット: `6f62843`
- 組み立て済みインスタンスの受け付けの出典コミット: `dea63aa`
- README とスコープの突合記録の出典コミット: `d97addd`
- 要求文脈での組み立て直しの出典コミット: `f6bffec`
- 空の `details` の拒否の出典コミット: `a63d14f`
