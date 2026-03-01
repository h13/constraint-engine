<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface CheckpointQueryInterface
{
    /** @return array<array{checkpointId: int, sessionId: string, taskContext: string, tag: string, confidence: string, dateCreated: string}> */
    #[DbQuery('checkpoint_list')]
    public function list(): array;

    /** @return array{checkpointId: int, sessionId: string, taskContext: string, aiProposal: string, humanFinal: string, diff: string, tag: string, confidence: string, dateCreated: string}|null */
    #[DbQuery('checkpoint_item', type: 'row')]
    public function item(int $id): array|null;

    /** @return array{totalCheckpoints: int, factualCount: int, strategicCount: int, stylisticCount: int}|null */
    #[DbQuery('checkpoint_summary', type: 'row')]
    public function summary(): array|null;

    /** @return array<array{tag: string, count: int}> */
    #[DbQuery('checkpoint_tag_distribution')]
    public function tagDistribution(): array;

    /** @return array<array{tag: string, date: string, count: int}> */
    #[DbQuery('checkpoint_trend')]
    public function trend(string $periodStart, string $periodEnd): array;

    /** @return array<array{checkpointId: int, sessionId: string, taskContext: string, tag: string, confidence: string, dateCreated: string}> */
    #[DbQuery('checkpoint_filter')]
    public function filter(string $tag, string $sessionId): array;

    /** @return array<array{sessionId: string, taskContext: string, checkpointCount: int, factualCount: int, strategicCount: int, stylisticCount: int, firstCheckpoint: string, lastCheckpoint: string}> */
    #[DbQuery('session_list')]
    public function sessionList(): array;

    /** @return array<array{checkpointId: int, sessionId: string, taskContext: string, aiProposal: string, humanFinal: string, diff: string, tag: string, confidence: string, dateCreated: string}> */
    #[DbQuery('session_analysis')]
    public function sessionAnalysis(string $sessionId): array;

    /** @return array{sessionId: string, taskContext: string, checkpointCount: int, factualCount: int, strategicCount: int, stylisticCount: int}|null */
    #[DbQuery('session_analysis_summary', type: 'row')]
    public function sessionAnalysisSummary(string $sessionId): array|null;

    /** @return array{totalCheckpoints: int, factualCount: int, strategicCount: int, stylisticCount: int}|null */
    #[DbQuery('checkpoint_period_summary', type: 'row')]
    public function periodSummary(string $periodStart, string $periodEnd): array|null;

    /** @return array<array{date: string, total: int, factualCount: int, factualRate: float}> */
    #[DbQuery('checkpoint_factual_rate')]
    public function factualRate(string $periodStart, string $periodEnd): array;

    /** @return array<array{userId: string, checkpointCount: int, factualCount: int, strategicCount: int, stylisticCount: int, firstCheckpoint: string, lastCheckpoint: string}> */
    #[DbQuery('team_summary')]
    public function teamSummary(): array;

    /** @return array<array{checkpointId: int, sessionId: string, taskContext: string, aiProposal: string, humanFinal: string, diff: string, tag: string, confidence: string, dateCreated: string}> */
    #[DbQuery('checkpoint_detail_list')]
    public function detailList(): array;

    /** @return array<array{checkpointId: int, sessionId: string, taskContext: string, aiProposal: string, humanFinal: string, diff: string, tag: string, confidence: string, dateCreated: string}> */
    #[DbQuery('checkpoint_stylistic')]
    public function stylisticCheckpoints(): array;
}
