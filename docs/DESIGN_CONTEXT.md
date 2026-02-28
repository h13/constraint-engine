# Constraint Engine — 設計経緯と開発コンテキスト

このドキュメントは、Constraint Engineの着想から現在の設計に至るまでの全ての議論・判断・進化を記録したものである。Claude Code での開発時に参照すること。

---

## 1. 着想の起点

### きっかけ
Zack Shapiro の「Claude-Native Law Firm」記事を読み、以下の洞察が生まれた:

- AIスキルはすぐにコモディティ化する
- AIが「法律文書を書けるか」はもう差別化にならない
- **差別化の本質は「AIに何を書かせるか」「何を修正するか」の判断**
- この判断パターンは現在、構造化されず記録もされていない

### 思想的背景
ALPS/ASD（Application-Level Profile Semantics）、BEAR.Sunday、REST制約アーキテクチャの学習経験から:

- **制約がアーキテクチャを定義する**（RESTの本質）
- **Gitのように差分を記録する** — ただしコードではなく「判断」の差分
- AIが提案(Proposal) → 人間が修正(Final) → その差分(Diff)が「その人/チームのConstraint」

---

## 2. コアコンセプト

### Checkpoint
AI提案と人間の最終判断の差分を記録する最小単位。

```
Checkpoint = {
  aiProposal: "Textフィールドを使用",
  humanFinal: "LongTextAreaに変更",
  diff: "Text→LongTextArea",
  tag: "factual",          // 分類
  confidence: "estimated", // 確信度
  taskContext: "Salesforce項目設計",
  sessionId: "session-001"
}
```

### 3分類フレームワーク

| 分類 | 定義 | 例 |
|------|------|-----|
| **Factual** | AIの事実誤りの修正 | API仕様値の訂正、技術的正確性 |
| **Strategic** | ビジネス判断・方針の変更 | クライアント事情での設計変更、コスト判断 |
| **Stylistic** | 表現・フォーマットの調整 | 用語統一、社内テンプレート適合 |

**理論的根拠**: この3分類は、AI修正が「情報の正確性」「意思決定」「表現の好み」のどの層に属するかを分離する。Factualは学習データで改善可能、Strategicは組織固有で自動化困難、Stylisticはテンプレート化で解消可能。3つの改善経路が異なることが分類の存在意義。

---

## 3. 設計進化の記録（クリティカルレビュー6回分）

### 課題1: 「何を記録するか」が広すぎる
- **初期案**: AI×人間の全インタラクションを記録
- **批判**: 範囲が無限大。「Salesforce設計時のAI修正」ですら広い
- **最終判断**: PoCでは「1人の自分 × Salesforce設計 × 2週間」に限定。ドメインを絞ることで検証精度を上げる

### 課題2: ALPSの適用レベル
- **初期案**: ALPSプロファイルそのものをConstraintの記述形式に使う
- **批判**: ALPSはAPI設計ツールであり、判断パターンの記述言語として使うのは哲学の誤用
- **最終判断**: ALPSは内部アーキテクチャの設計言語として使う。制約プロファイルの出力形式はシンプルなJSONに。ユーザーがALPSを知る必要はない

### 課題3: 防御可能性（Moat）の不在
- **批判**: Checkpointの記録→可視化は、Notion+タグ付けで代替可能。プロダクトとしてのMoatがない
- **応答**: Moatは「継続的記録から生まれる時系列データの蓄積」。手動タグ付けでは続かない。MCPサーバーによるシームレスな記録が差別化
- **再批判**: それは「UIの便利さ」であり、構造的Moatではない
- **最終判断**: 正直にMoatは弱いと認める。だからこそBootstrapアプローチ（まず自分で使い、記事を書き、共鳴者を集める）を採用。VC的スケーラビリティは追わない

### 課題4: Primary Valueの矛盾
- **初期案**: コンプライアンス（監査記録）を主要価値として訴求
- **批判**: コンプライアンスは「買わなければいけない理由」であって「使いたい理由」ではない。記録の手間をかけてまで欲しいものではない
- **最終判断**: Primary Value = **判断パターンの可視化**（「自分はStrategicな修正が多い」と気づくこと）。コンプライアンスはTertiary Value

### 課題5: 記録の摩擦
- **批判**: どんなに価値があっても、毎回手動で記録するのは続かない
- **最終判断**: MCPサーバーが自動でdiff検出・分類。人間のアクションは「record_checkpoint を呼ぶ」だけ。分類はAIが推定し、人間は必要に応じて修正

### 課題6: 「考えるのをやめて作れ」
- **批判**: ドキュメントv0.3の時点で「哲学的には正しいが製品として未検証。次のステップは思考ではなく実装」
- **最終判断**: 同意。PoCを作って2週間使い、定量的Go/No-Go基準で判断する

---

## 4. Go/No-Go基準

2週間のPoC使用後、以下3つを定量評価:

1. **想起価値**: 過去のCheckpointを見返して業務判断に活用した回数 **≥ 3回**
2. **発見価値**: 分類分布に予想外の傾向が **1つ以上** 見つかった
3. **摩擦許容**: 「邪魔だからやめたい」と思った回数 **≤ 2回**

→ 3つとも満たせば Go（同僚検証フェーズへ）
→ 3つとも No なら棚上げ（インサイト記事だけ書く）

---

## 5. GTM戦略

### ターゲットペルソナ

| 優先度 | ペルソナ | 痛み | 価値提案 |
|--------|---------|------|---------|
| Primary | SIチームリード | メンバーの判断品質が見えない | 判断パターンの可視化でメンタリング精度向上 |
| Secondary | マネージャー | AI活用の効果測定ができない | Checkpoint蓄積で「AIがどこで役立ったか」を定量化 |
| Tertiary | コンプライアンス | AI利用の監査記録がない | 自動記録でエビデンス確保 |

### チャネル戦略

自分で使う → 記事を書く → SI業界の同僚に共有 → コミュニティ形成

### メッセージング（30秒ピッチ）
> AIが書いた提案書、あなたは必ず直しますよね。
> その「直し」にこそ、あなたの専門性がある。
> Constraint Engineは、その修正パターンを自動で記録・分類し、
> あなたの判断の傾向を可視化します。

### 価格戦略
- 記録は無料（摩擦を最小化）
- 分析・可視化は有料（価値が証明された後に課金）

---

## 6. アーキテクチャ決定

### Phase 0 撤廃の理由

当初はTypeScript MCP Server + フラットJSONファイルの最小PoCを計画していたが、撤廃した。理由:

1. **BEAR.Skillsの存在**: `bear-from-alps`でALPSプロファイルからプロジェクト全体を自動生成できるため、「最小限TypeScript」と「BEAR.Sundayフル構成」のコスト差がほぼゼロ
2. **思想の検証にならない**: フラットJSONでの検証は「ファイルに書き込んで読み出せた」しか証明しない。リソース指向で制約プロファイルが一級市民として振る舞うかは、BEAR.Sundayで作らないと見えない
3. **開発プロセス自体が検証データ**: BEAR.Sundayプロジェクトの開発（bear-from-alpsの生成物をレビュー・修正する過程）が、Constraint Engineが記録すべき最初のCheckpointデータになる

### 現在のアーキテクチャ

```
MCP Server (TypeScript)          BEAR.Sunday (PHP)
┌─────────────────────┐         ┌──────────────────────────┐
│ diff検出             │         │ Checkpoint Resource      │
│ 3分類AI判定          │ ──POST──→ │ PatternDashboard Resource│
│ Claude Desktop連携   │         │ CheckpointList Resource  │
└─────────────────────┘         │ Query/Command Interface  │
  薄いクライアント                │ SQLite DB               │
                                └──────────────────────────┘
                                  ALPSプロファイル駆動
```

- **MCP Server**: diff検出と分類判定だけ。データ管理はしない
- **BEAR.Sunday**: Checkpointのライフサイクル管理、集計、可視化。ALPSが設計を駆動
- **接続**: REST API（Uniform Interface）。MCP Serverが`POST /checkpoint`、`GET /pattern-dashboard` を呼ぶ

### なぜBEAR.Sundayか

1. **哲学の一致**: 「すべてはリソースであり、フレームワークは制約の集合」= Constraint Engineの思想そのもの
2. **ALPS双方向フロー**: `bear-from-alps`（ALPS→コード）と`bear-to-alps`（コード→ALPS）が、AI提案→人間修正→セマンティック記録のフローと重なる
3. **Ray.MediaQuery**: インターフェースに`#[DbQuery]`を付けるだけでSQL実行が自動生成される。Checkpointの読み書きに完璧にフィット
4. **bear-review**: 生成コードの品質チェックが自動化されている。「リソースは判断だけ。作り方はサービスに委譲」の原則を強制できる

---

## 7. リソース設計

ALPSプロファイル（`docs/alps.json`）に定義済み。

### Ontology（15要素）
checkpointId, sessionId, taskContext, aiProposal, humanFinal, diff, tag, confidence, dateCreated, totalCheckpoints, factualCount, strategicCount, stylisticCount, periodStart, periodEnd

### Taxonomy（3状態）
- **CheckpointList**: 一覧（tag/sessionIdでフィルタ可能）
- **Checkpoint**: 個別詳細（aiProposal, humanFinal, diffを含む）
- **PatternDashboard**: 分類分布・トレンドの集計表示

### Choreography（4遷移）
- goCheckpointList (safe): フィルタ付き一覧取得
- goCheckpoint (safe): 詳細取得
- doRecordCheckpoint (unsafe): 新規記録
- goPatternDashboard (safe): 集計表示

### 状態遷移
```
CheckpointList ←→ PatternDashboard
       ↓
   Checkpoint
```

---

## 8. v0.8ドキュメントの主要決定事項

5ラウンドのクリティカルレビューを経て確定した5つの設計判断:

1. **ドメイン限定**: SI業界 × Salesforce設計に特化してPoCを行う
2. **Bootstrap GTM**: 自分→記事→同僚→コミュニティの段階的拡大
3. **判断可視化が主価値**: コンプライアンスは副次的（Tertiary）
4. **3分類フレームワーク**: Factual/Strategic/Stylistic（改善経路が異なる）
5. **定量的Go/No-Go**: 感覚ではなく3つの数値基準で判断

---

## 9. 会話の原文トランスクリプト

以下のファイルに完全な会話記録がある（Claude.ai上の元会話から書き出したJSON形式）:

1. `transcripts/01-concept-origin.txt` — 着想〜初期コンセプト〜最初の批判的レビュー
2. `transcripts/02-critical-review-evolution.txt` — 3層クリティカルレビュー、6つの根本課題、設計進化
3. `transcripts/03-v03-gtm-strategy.txt` — v0.3ドキュメント生成、GTM戦略統合
4. `transcripts/04-v08-5round-review.txt` — 5ラウンドレビュー、v0.8最終版、Claude Code実装手順

---

## 10. 用語集

| 用語 | 定義 |
|------|------|
| Checkpoint | AI提案と人間の最終判断の差分を記録する最小単位 |
| Constraint Profile | 蓄積されたCheckpointから浮かび上がる判断パターン |
| 3分類 | Factual / Strategic / Stylistic の判断カテゴリ |
| Constraint Engine | この製品全体の名称 |
| ALPS | Application-Level Profile Semantics。APIのセマンティクスを定義する仕様 |
| bear-from-alps | ALPSプロファイルからBEAR.Sundayプロジェクトを生成するClaude Codeスキル |
| Ray.MediaQuery | PHPインターフェースにアノテーションを付けるだけでSQL実行を自動バインドする仕組み |
