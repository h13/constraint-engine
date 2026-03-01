<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

class PatternDashboard extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    public function onGet(
        string $periodStart = '',
        string $periodEnd = '',
        string $compareStart = '',
        string $compareEnd = '',
    ): static {
        $this->body = [
            'summary' => $this->query->summary(),
            'tagDistribution' => $this->query->tagDistribution(),
            'trend' => $periodStart !== '' && $periodEnd !== ''
                ? $this->query->trend($periodStart, $periodEnd)
                : [],
            'comparison' => $this->buildComparison($periodStart, $periodEnd, $compareStart, $compareEnd),
        ];

        return $this;
    }

    /** @return array{current: array|null, previous: array|null}|null */
    private function buildComparison(
        string $periodStart,
        string $periodEnd,
        string $compareStart,
        string $compareEnd,
    ): array|null {
        if ($periodStart === '' || $periodEnd === '' || $compareStart === '' || $compareEnd === '') {
            return null;
        }

        return [
            'current' => $this->query->periodSummary($periodStart, $periodEnd),
            'previous' => $this->query->periodSummary($compareStart, $compareEnd),
        ];
    }
}
