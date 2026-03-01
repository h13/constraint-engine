<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\GoNoGoVerdict;
use ConstraintEngine\App\Query\RecallQueryInterface;

class GoNoGo extends ResourceObject
{
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
            'recallTarget' => GoNoGoVerdict::RECALL_TARGET,
            'discoveryCount' => $discovery,
            'discoveryTarget' => GoNoGoVerdict::DISCOVERY_TARGET,
            'frictionCount' => $friction,
            'frictionLimit' => GoNoGoVerdict::FRICTION_LIMIT,
            'verdict' => GoNoGoVerdict::compute($recall, $discovery, $friction),
        ];

        return $this;
    }
}
