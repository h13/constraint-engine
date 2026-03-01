<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
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

    #[Link(rel: 'goCheckpointList', href: '/checkpoints')]
    public function onGet(): static
    {
        $summary = $this->query->summary();
        $recall = $summary !== null ? (int) $summary['recallCount'] : 0;
        $discovery = $summary !== null ? (int) $summary['discoveryCount'] : 0;
        $friction = $summary !== null ? (int) $summary['frictionCount'] : 0;

        $this->body = [
            'recallCount' => $recall,
            'recallTarget' => self::RECALL_TARGET,
            'discoveryCount' => $discovery,
            'discoveryTarget' => self::DISCOVERY_TARGET,
            'frictionCount' => $friction,
            'frictionLimit' => self::FRICTION_LIMIT,
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
