# CLAUDE.md

## プロジェクト概要

Constraint Engine — AI×人間の協働における判断プロセスの記録・可視化プラットフォーム。
BEAR.Sunday + BEAR.Skills で構築する。ALPSプロファイル駆動。

**必ず最初に読むこと**: `docs/DESIGN_CONTEXT.md` に設計経緯・判断の全記録がある。
原文の会話トランスクリプトは `docs/transcripts/` にある。

## セットアップ手順

`docs/alps.xml` にALPSプロファイルがある。以下の Phase を順に実行すること。

### Phase A: BEAR.Sundayプロジェクト生成

1. `asd --validate docs/alps.xml` で検証
2. `/bear-from-alps` を実行:
   - ALPSプロファイル: `docs/alps.xml`
   - Development Approach: **Outside-In (Full Stack)**
   - Project Directory: **現在のディレクトリ**
   - Vendor Name: **ConstraintEngine**
   - Package Name: **App**
   - Router: **Aura Router**（`/checkpoints/{id}` のパスパラメータが必要）

### Phase B: 生成コードのレビューと修正

`/bear-review` で生成された全リソースをレビュー。以下を確認:

- Checkpoint Resource の `onPost` が `201 + Location` を返しているか
- body assignment が一括代入 `$this->body = [...]` になっているか
- PatternDashboard の集計ロジックがドメイン層に委譲されているか（リソース内にループ禁止）

### Phase C: パターン集計ドメインロジック

`src/Query/CheckpointQueryInterface.php` に集計メソッドを追加:

```php
#[DbQuery('checkpoint_tag_distribution')]
public function tagDistribution(): array;

#[DbQuery('checkpoint_trend')]
public function trend(string $periodStart, string $periodEnd): array;
```

対応SQL:

```sql
-- var/sql/checkpoint_tag_distribution.sql
SELECT tag, COUNT(*) as count FROM checkpoint GROUP BY tag;

-- var/sql/checkpoint_trend.sql
SELECT
  tag,
  DATE(date_created) as date,
  COUNT(*) as count
FROM checkpoint
WHERE date_created BETWEEN :periodStart AND :periodEnd
GROUP BY tag, DATE(date_created)
ORDER BY date;
```

`PatternDashboard` リソースはこのインターフェースを注入して集計結果を返す。

### Phase D: MCP Server（TypeScript）

`mcp-server/` ディレクトリに TypeScript MCP サーバーを作成。

役割: **AI対話のdiff検出 → 3分類の自動判定 → BEAR.Sunday APIへのPOST**

依存: `@modelcontextprotocol/sdk`, `@anthropic-ai/sdk`

MCPツール:

1. **record_checkpoint**
   - 入力: `aiProposal`, `humanFinal`, `taskContext`, `sessionId`
   - 処理: diff検出 → Claude API (claude-sonnet-4-5-20250929) で3分類判定 → `POST http://localhost:8080/checkpoint` に送信
   - 分類プロンプト: 差分テキストを受け取り、`{"tag": "factual"|"strategic"|"stylistic", "confidence": "estimated"}` だけ返す

2. **show_pattern**
   - 処理: `GET http://localhost:8080/pattern-dashboard` を取得
   - 出力: 分類分布のサマリーテキスト

### Phase E: 動作確認

```bash
composer setup            # DB migration
composer test             # テスト全パス
composer serve            # dev server at localhost:8080

# 別ターミナル
curl -X POST http://localhost:8080/checkpoint \
  -H "Content-Type: application/json" \
  -d '{"sessionId":"test-001","taskContext":"Salesforce項目設計","aiProposal":"Textフィールドを使用","humanFinal":"LongTextAreaに変更","diff":"Text→LongTextArea","tag":"factual","confidence":"estimated"}'

curl http://localhost:8080/checkpoints
curl http://localhost:8080/checkpoints/{id}
curl http://localhost:8080/pattern-dashboard
```

### Phase F: ALPS再抽出

`/bear-to-alps` で実装後のリソース構造からALPSプロファイルを再抽出。
最初の `docs/alps.xml` と比較して設計ドリフトがないか確認。

## アーキテクチャ原則

- **ALPSが先、コードは後。** セマンティクスを定義してからコードを生成する
- **リソースは判断だけ。** 「何を返すか」を決める。「どう作るか」はドメイン層に委譲
- **MCP Serverは薄いクライアント。** diff検出と分類判定だけ。データ管理はBEAR.Sunday
- **制約がアーキテクチャを定義する。** BEAR.Sundayのフレームワーク制約がコード品質を保証する

## 3分類フレームワーク

| 分類 | 定義 | 例 |
|------|------|-----|
| Factual | AIの事実誤りの修正 | API仕様値の訂正、技術的正確性 |
| Strategic | ビジネス判断・方針の変更 | クライアント事情での設計変更、コスト判断 |
| Stylistic | 表現・フォーマットの調整 | 用語統一、社内テンプレート適合 |

## 開発コマンド

```bash
composer test       # PHPUnit
composer cs         # コーディング規約チェック
composer cs-fix     # 自動修正
composer sa         # 静的解析
composer serve      # dev server
composer build      # 全チェック + ビルド
```
