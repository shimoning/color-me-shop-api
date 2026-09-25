# ADR 0015: 顧客書き込み API の入力を作成と更新で分ける

- 状態: 採用
- 決定日: 2026-09-25

## 文脈

現行ライブラリの顧客 API は読み取り系だけを公開しており、`Services\Customer` には作成用メソッドの
TODO コメントだけが残っていた。出典: `bbff143`。

2026-09-24 に取得した[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) で、顧客の書き込み
3操作を確認した。いずれも必要な scope は `write_sales` であり、顧客専用の scope は存在しない。

| 操作 | 要求ボディ | 成功応答 | 失敗応答 |
| --- | --- | --- | --- |
| `POST /v1/customers` | `customer` object。`customer` 自体と、その子の `name` / `mail` / `pref_id` / `postal` / `address1` / `tel` が required。作成専用の `add_member` を持つ | `200`、JSON の `customer` | `422` |
| `PUT /v1/customers/{customer_id}` | `customer` object。`customer` 自体は required だが子プロパティに required 指定はない。更新専用の `sex` / `tel_mobile` を持つ | `200`、JSON の `customer` | `404`、`422` |
| `POST /v1/customers/{customer_id}/points` | `points` をトップレベルに持つ object。`points` は required かつ `nullable: false` の integer | `200`、`customer_id` と `points` をトップレベルに持つ JSON | 定義なし |

[ADR 0014](0014-model-product-write-api.md) は商品の作成と更新で入力 Entity を共用する判断をした。その
根拠は、両 `product` object の `required` 指定が同一（いずれも指定なし）で、必須性の差を型で表せない
ことだった。顧客では作成側にだけ6つの required 指定があり、必須性の差を型で表せる。作成専用の
`add_member` と更新専用の `sex` / `tel_mobile` という、有効なプロパティ集合の差もある。

なお、以前の観測時点では公式 OpenAPI の全スキーマで `required` が空だったが、2026-09-24 取得分では
顧客の書き込み系に required 指定があった。

顧客を削除する API は公式に存在しない。商品と同じく、実 API の検証で作成したデータはショップに残る。

2026-09-25 にテスト用ショップで実 API を観測した。詳細は
[顧客 API 応答構造の実測記録](../api-customer-structure.md)にある。公式 OpenAPI と食い違う挙動を
5点確認した。

- 更新の実 API は `name` と `address1` を必須とし、欠けると必ず `422`（code `422007`）になる。
  公式 OpenAPI の更新 request には required 指定がない。`mail` / `pref_id` / `postal` / `tel` は
  いずれも省略して `200` だった。省略したフィールドは保持され、部分更新として機能した。
- `sex` は公式 OpenAPI の作成 request にないが、作成時に送ると反映された。
- `tel_mobile` は公式 OpenAPI の更新 request にあるが、`null` → 値 → 別の値 → `null` のいずれの
  パターンでも `200` を返しながら、PUT 応答と直後の GET がともに `null` のままだった。
- フリガナは、公式 OpenAPI のパターン `^[ァ-ヶー 　ヷヸヹヺ]*$` が許容する `ヷ`（U+30F7）〜
  `ヺ`（U+30FA）の4文字を `422`（code `422250`）で拒否した。残り89文字と空文字は `200` だった。
- ポイント増減は `points` に文字列 `"100"` を送っても `200` で受理した。`points` の欠落と `null` は
  `422`（code `422100`）、保有ポイントを超える減算は `422`（code `422014`）だった。

同じ観測で、顧客応答に公式 OpenAPI の `customer` スキーマにない `memo` キーが常に存在した。
`memo` は書き込めなかった。`points` / `member` / `sales_count` / `make_date` / `update_date` /
`pref_name` / `account_id` も、要求に含めても無視された。

## 判断

- 顧客の作成と更新は、`Entities\Customer\CustomerCreateInput` と
  `Entities\Customer\CustomerUpdateInput` の別々の入力 Entity で表す。両操作で required 指定と
  有効なプロパティ集合が異なり、その差を型で表せるためである。ADR 0014 が商品で共用を選んだ
  根拠（必須性の差を型で表せない）は顧客には当てはまらない。出典: `2fdf813`、`3711dc1`。
- ポイント増減の入力は `Entities\Customer\CustomerPointsInput` とする。応答は他の顧客 API と異なり
  `customer` などのキーで包まれないため、`Entities\Customer\Points` という専用の応答 Entity で表す。
  `customer_id` と、増減後の保有ポイント数である `points` を持つ。出典: `d19bf38`。
- 作成・更新の応答 `customer` は、公式 OpenAPI 上も実測上も GET の `customer` と同じ形であるため、
  既存の `Entities\Customer\Customer` を再利用する。出典: `2fdf813`、`3711dc1`。
- 必須フィールドの送信前検証は Service 側に置く。`Services\Customer::create()` は公式 OpenAPI が
  required とする6フィールド、`update()` は実測で必須だった `name` と `address1`、`changePoints()` は
  `points` の明示を確認し、欠けていれば `ParameterException` で拒否する。各入力 Entity は
  `REQUIRED_FIELDS` として対象を公開するだけで、自身では検証しない。
  出典: `2fdf813`（作成）、`f2aad6b`（更新）、`d19bf38`（ポイント）。
- 検証を入力 Entity のコンストラクタに置かないのは、空配列での構築を前提とする `EntityContractTest` の
  横断契約を保つためである。ADR 0014 のピックアップ入力（`requirePickupFields`）と同じ置き場所になる。
  出典: `2fdf813`。
- 更新の必須検証は、公式 OpenAPI の required 指定ではなく実測に基づく。ADR 0014 が定めた「OpenAPI が
  要求ボディを required とし、かつ API がそのフィールドで操作対象を特定するものに限る」という基準の
  外にあるため、この差異を実装の PHPDoc に明記する。出典: `f2aad6b`。
- `CustomerCreateInput` は `sex` を持つ。公式 OpenAPI の作成 request にはないが、実測で反映された
  ためである。`tel_mobile` / `memo` / `points` / `member` / `sales_count` は実測で無視されたため
  持たせない。出典: `4e6d6c2`。
- `CustomerUpdateInput` は `tel_mobile` を持たない。公式 OpenAPI の更新 request にはあるが、実測で
  書き込めなかったためである。ADR 0014 が商品の `unlisted` を `ProductInput` から外したのと同じ判断で
  ある。書き込めないフィールドを入力に残すと、利用者は黙って無視される値を送ることになる。
  出典: `586179a`。
- `CustomerUpdateInput` の null 許容は公式 OpenAPI の `nullable` 指定に従う。`name` / `mail` /
  `pref_id` / `postal` / `address1` / `tel` は nullable 指定がなく、作成では required でもあるため、
  明示した `null` を型として拒否する。ADR 0014 が `ProductInput` の全フィールドを nullable にした
  判断は、この Entity には適用しない。nullable なフィールドは実測で明示した `null` によりクリア
  できた（`receive_mail_magazine` だけは `null` を送ると `false` になった）。出典: `3711dc1`。
- `sex` には応答と同じ `Constants\Sex` を共用する。要求側では未知値のフォールバックを適用せず、
  番兵の `UNKNOWN` と未定義の文字列を構築時に拒否する。[ADR 0013](0013-expand-opt-in-enum-fallback.md)
  の要求側契約を維持する判断である。実測でも未定義値は `422` で拒否された。
  出典: `3711dc1`、`4e6d6c2`。
- `pref_id` には応答と同じ `Constants\Prefecture` を共用する。1〜48 という公式 OpenAPI の範囲と、
  enum の case が一致する。出典: `2fdf813`。
- `Values\Furigana` の許容文字は `^[ァ-ヶー 　]*$` とする。公式 OpenAPI のパターンより狭いが、
  実 API が `ヷヸヹヺ` を拒否したためである。[ADR 0012](0012-allow-nullability-from-api-observations.md)
  と同じく、公式定義と実測が食い違う場合は実測を採る。空文字は実測で受理されたため許容する。
  この Value は応答側でも使うため、利用者にとっては検証が緩む方向（空文字の許容）と厳しくなる方向
  （`ヷヸヹヺ` の拒否）の両方の挙動変更になる。出典: `05029e1`、`5086b51`。
- `CustomerPointsInput` の `points` は非 null の int として宣言する。公式 OpenAPI が
  `nullable: false` とし、実測でも欠落と `null` がともに `422` になったためである。実 API は文字列の
  `"100"` も受理したが、ライブラリは整数だけを受け付ける。要求側を厳格に保つ既存の方針に従う。
  出典: `d19bf38`。
- 顧客を削除する API は提供しない。公式 API に該当操作がないためである。実 API の検証で作成した
  顧客はショップに残る。これは削除不能による検証環境の運用であり、ライブラリの仕様ではない。
  出典: 公式 OpenAPI（2026-09-24 取得）、`03463df`（削除 API がないことと、検証で作成した顧客が
  残ることの観測記録）、`2fdf813`、`3711dc1`、`d19bf38`（削除メソッドを持たない Service の実装）。

## 代替案と却下理由

- 作成と更新で1つの入力 Entity を共用する案は、ADR 0014 の商品と揃う。しかし作成側の6つの required
  指定を型で表せず、`add_member` と `sex` のどちらがどの操作で有効かも型から読み取れないため採用
  しない。共用を選んだ商品とは前提が異なる。
- 必須フィールドの検証を入力 Entity のコンストラクタに置く案は、インスタンスが常に有効であることを
  保証でき、誤りをより早く捉えられる。しかし全 Entity が空配列で構築できることを前提にした
  `EntityContractTest` の横断契約を崩す。1クラスのために4つの契約テストへ除外機構を入れる費用に
  見合わないため採用しない。
- 更新の必須検証を行わず API の `422` に委ねる案は、公式 OpenAPI に required 指定がない以上は
  忠実である。しかし実測で必ず失敗する要求を送ることになり、往復が1回無駄になるため採用しない。
  実測に基づく検証であることを PHPDoc に明記して補う。
- `tel_mobile` を入力に残し、書き込めないことを文書だけに記す案は、将来 API 側が対応したときに変更が
  不要である。しかし現時点では黙って無視される値を受け付けることになるため採用しない。
- ポイント増減の入力を Entity にせず、Service の引数を `int` にする案は単純である。しかし他の書き込み
  系と形が揃わず、`REQUIRED_FIELDS` による送信前検証の置き場所も失うため採用しない。
- ポイント増減の応答を `Entities\Customer\Customer` で表す案は、応答が `customer_id` と `points` しか
  持たないため成立しない。増減後の保有ポイント数という意味も表せない。
- `Values\Furigana` を公式 OpenAPI のパターンのままにして `ヷヸヹヺ` の拒否を API に委ねる案は、将来
  API 側が OpenAPI に追いついたときに変更が不要である。しかし現時点では必ず `422` になる値を
  ライブラリが通すことになるため採用しない。

## 帰結

顧客の作成・更新・ポイント増減が、操作ごとの入力 Entity と応答 Entity で表現できる。作成と更新で
有効なフィールド集合が型として分かれるため、利用者は操作ごとの有効フィールドを公式 API 契約から
読み解く必要がない。

公式 OpenAPI と実 API が食い違う5点については実測を採ったため、ライブラリの契約は公式定義と一致
しない。該当箇所の PHPDoc に差異と観測日を明記してあり、API 側の挙動が変われば再検討が必要になる。

`Values\Furigana` の検証変更は応答側にも及ぶ。空文字を持つ顧客を読めるようになる一方、`ヷヸヹヺ` を
含むフリガナが応答に現れた場合は `ParameterException` になる。実 API がそれらを保存できない以上、
応答に現れることはないと考えられるが、管理画面など API 以外の経路で設定された値は未検証である。

顧客を削除できないため、実 API の検証で作成したデータは残る。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0006: Entity のプロパティ解決を通常のインスタンスプロパティに統一する](0006-limit-entity-property-kinds.md)
- [ADR 0012: 実 API の観測に基づき Entity の null 許容を判断する](0012-allow-nullability-from-api-observations.md)
- [ADR 0013: 応答に使う enum のフォールバック対象を拡張する](0013-expand-opt-in-enum-fallback.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [顧客 API 応答構造の実測記録](../api-customer-structure.md)（出典コミット: `03463df`）
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-24 取得）
- 顧客の作成・更新・ポイント増減の実装の出典コミット: `2fdf813`、`3711dc1`、`d19bf38`
- 実測に基づく調整の出典コミット: `5086b51`（フリガナ）、`4e6d6c2`（作成の `sex`）、
  `586179a`（更新の `tel_mobile`）、`f2aad6b`（更新の必須検証）、`5958161`（エラーコード）
