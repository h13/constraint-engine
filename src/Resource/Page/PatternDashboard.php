<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\DateHelper;
use ConstraintEngine\App\Query\CheckpointQueryInterface;
use InvalidArgumentException;

class PatternDashboard extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    #[Link(rel: 'goCheckpointList', href: '/checkpoints')]
    public function onGet(
        string $periodStart = '',
        string $periodEnd = '',
        string $compareStart = '',
        string $compareEnd = '',
    ): static {
        try {
            $endExcl = $periodEnd !== '' ? DateHelper::nextDay($periodEnd) : '';
        } catch (InvalidArgumentException) {
            $this->code = 400;
            $this->body = ['error' => 'Invalid periodEnd date format. Use YYYY-MM-DD.'];

            return $this;
        }

        $this->body = [
            'summary' => $this->query->summary(),
            'tagDistribution' => $this->query->tagDistribution(),
            'trend' => $periodStart !== '' && $endExcl !== ''
                ? $this->query->trend($periodStart, $endExcl)
                : [],
            'comparison' => $this->buildComparison($periodStart, $periodEnd, $compareStart, $compareEnd),
            'factualRate' => $periodStart !== '' && $endExcl !== ''
                ? $this->query->factualRate($periodStart, $endExcl)
                : [],
        ];

        return $this;
    }

    /** @return array{current: array<string, int>|null, previous: array<string, int>|null}|null */
    private function buildComparison(
        string $periodStart,
        string $periodEnd,
        string $compareStart,
        string $compareEnd,
    ): array|null {
        if ($periodStart === '' || $periodEnd === '' || $compareStart === '' || $compareEnd === '') {
            return null;
        }

        try {
            return [
                'current' => $this->query->periodSummary($periodStart, DateHelper::nextDay($periodEnd)),
                'previous' => $this->query->periodSummary($compareStart, DateHelper::nextDay($compareEnd)),
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
