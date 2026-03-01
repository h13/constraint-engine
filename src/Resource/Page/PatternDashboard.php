<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

use function date;
use function strtotime;

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
        $endExcl = $periodEnd !== '' ? self::nextDay($periodEnd) : '';
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

    private static function nextDay(string $date): string
    {
        return date('Y-m-d', strtotime($date . ' +1 day'));
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

        return [
            'current' => $this->query->periodSummary($periodStart, self::nextDay($periodEnd)),
            'previous' => $this->query->periodSummary($compareStart, self::nextDay($compareEnd)),
        ];
    }
}
