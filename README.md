# Constraint Engine

AI×人間の協働における判断プロセスの記録・可視化プラットフォーム。

人間が AI の提案をどう修正したかを **3分類**（Factual / Strategic / Stylistic）で自動判定し、パターンとして蓄積する。

## アーキテクチャ

```
┌─────────────────────────────────────┐
│         Docker Compose              │
│                                     │
│  nginx ─→ php-fpm (BEAR.Sunday)    │
│              │                      │
│              ▼                      │
│          PostgreSQL                 │
│                                     │
│  php mcp-server (stdio, 同一イメージ) │
└─────────────────────────────────────┘
```

| コンポーネント | 技術 |
|---------------|------|
| API | BEAR.Sunday (PHP 8.5) |
| DB | PostgreSQL 16 |
| Web Server | nginx + php-fpm |
| MCP Server | PHP (mcp/sdk, stdio) |
| Diff 分類 | Anthropic Messages API (Claude Sonnet) |

## セットアップ

前提: Docker と Docker Compose がインストール済み。ローカルに PHP は不要。

```bash
cp .env.example .env
# .env を編集（ANTHROPIC_API_KEY 等）

make build
make setup
```

`make setup` は以下を実行する:
1. コンテナ起動
2. `composer install`（コンテナ内）
3. PostgreSQL にテーブル作成

## 開発

すべてのコマンドはコンテナ内で実行される。

```bash
make up          # コンテナ起動
make test        # PHPUnit
make cs          # コーディング規約チェック
make cs-fix      # 自動修正
make sa          # 静的解析 (Psalm + PHPStan)
make shell       # app コンテナに入る
make db-shell    # psql 接続
make logs        # ログ表示
make health      # ヘルスチェック
make down        # コンテナ停止
```

全コマンド一覧: `make help`

## API エンドポイント

```bash
# ヘルスチェック
curl http://localhost:8080/health

# チェックポイント一覧
curl http://localhost:8080/checkpoints

# チェックポイント作成
curl -X POST http://localhost:8080/checkpoints \
  -d 'sessionId=test-001&taskContext=Salesforce項目設計&aiProposal=Textフィールドを使用&humanFinal=LongTextAreaに変更&diff=Text→LongTextArea&tag=factual&confidence=estimated'

# 個別取得
curl http://localhost:8080/checkpoints/1

# パターンダッシュボード
curl http://localhost:8080/pattern-dashboard
```

## MCP Server

Claude Desktop から直接利用可能。`claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "constraint-engine": {
      "command": "docker",
      "args": ["compose", "exec", "-T", "app", "php", "bin/mcp-server.php"],
      "cwd": "/path/to/constraint-engine"
    }
  }
}
```

ツール:
- **record_checkpoint** — AI 提案と人間の最終版の diff を検出し、3分類を自動判定して記録
- **show_pattern** — 分類分布のサマリーを表示

## 本番デプロイ

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

prod オーバーライドの差分:
- Dockerfile ターゲット: `runtime`（コンパイル済み、xdebug なし）
- ボリュームマウントなし（イメージ内にコード同梱）
- DB ポート非公開

## 3分類フレームワーク

| 分類 | 定義 | 例 |
|------|------|-----|
| Factual | AI の事実誤りの修正 | API 仕様値の訂正、技術的正確性 |
| Strategic | ビジネス判断・方針の変更 | クライアント事情での設計変更 |
| Stylistic | 表現・フォーマットの調整 | 用語統一、社内テンプレート適合 |

## プロジェクト構造

```
src/
  Mcp/                    # MCP Server ツール
  Module/                 # DI モジュール (App, Prod, Mcp)
  Query/                  # Ray.MediaQuery インターフェース
  Resource/Page/          # BEAR.Sunday リソース
  Semantic/               # セマンティック変数 (Tag, Confidence, SessionId)
bin/
  mcp-server.php          # MCP Server エントリポイント
  setup.php               # DB マイグレーション
docker/
  php/Dockerfile          # マルチステージ (dev / runtime)
  php/php.ini             # 本番用 PHP 設定
  php/php-dev.ini         # 開発用 PHP 設定
  nginx/default.conf      # nginx 設定
var/
  sql/                    # SQL ファイル
    sqlite/               # SQLite DDL (テスト用)
    pgsql/                # PostgreSQL DDL
compose.yaml              # Docker Compose (dev デフォルト)
compose.prod.yaml         # 本番オーバーライド
Makefile                  # 開発コマンド
```
