<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\RecallQueryInterface;

class GoNoGo extends ResourceObject
{
    private const int RECALL_TARGET = 3;
    private const int DISCOVERY_TARGET = 1;
    private const int FRICTION_LIMIT = 2;

    public function __construct(
        private readonly RecallQueryInterface $query,
    ) {
    }

    public function onGet(): static
    {
        $summary = $this->query->summary();
        $recall = $summary !== null ? (int) $summary['recall_count'] : 0;
        $discovery = $summary !== null ? (int) $summary['discovery_count'] : 0;
        $friction = $summary !== null ? (int) $summary['friction_count'] : 0;

        $this->body = [
            'recall_count' => $recall,
            'recall_target' => self::RECALL_TARGET,
            'discovery_count' => $discovery,
            'discovery_target' => self::DISCOVERY_TARGET,
            'friction_count' => $friction,
            'friction_limit' => self::FRICTION_LIMIT,
            'verdict' => $this->computeVerdict($recall, $discovery, $friction),
        ];

        return $this;
    }

    private function computeVerdict(int $recall, int $discovery, int $friction): string
    {
        if ($friction > self::FRICTION_LIMIT) {
            return 'no_go';
        }

        if ($recall >= self::RECALL_TARGET && $discovery >= self::DISCOVERY_TARGET) {
            return 'go';
        }

        return 'pending';
    }
}
