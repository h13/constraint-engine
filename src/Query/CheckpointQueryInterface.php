<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface CheckpointQueryInterface
{
    /** @return array<array{id: int, session_id: string, task_context: string, ai_proposal: string, human_final: string, diff: string, tag: string, confidence: string, date_created: string}> */
    #[DbQuery('checkpoint_list')]
    public function list(): array;

    /** @return array{id: int, session_id: string, task_context: string, ai_proposal: string, human_final: string, diff: string, tag: string, confidence: string, date_created: string}|null */
    #[DbQuery('checkpoint_item', type: 'row')]
    public function item(int $id): array|null;

    /** @return array{total: int, factual_count: int, strategic_count: int, stylistic_count: int}|null */
    #[DbQuery('checkpoint_summary', type: 'row')]
    public function summary(): array|null;

    /** @return array<array{tag: string, count: int}> */
    #[DbQuery('checkpoint_tag_distribution')]
    public function tagDistribution(): array;

    /** @return array<array{tag: string, date: string, count: int}> */
    #[DbQuery('checkpoint_trend')]
    public function trend(string $periodStart, string $periodEnd): array;

    /** @return array<array{id: int, session_id: string, task_context: string, ai_proposal: string, human_final: string, diff: string, tag: string, confidence: string, date_created: string}> */
    #[DbQuery('checkpoint_filter')]
    public function filter(string $tag, string $sessionId): array;
}
