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

    public function onGet(string $periodStart = '', string $periodEnd = ''): static
    {
        $this->body = [
            'summary' => $this->query->summary(),
            'tagDistribution' => $this->query->tagDistribution(),
            'trend' => $periodStart !== '' && $periodEnd !== ''
                ? $this->query->trend($periodStart, $periodEnd)
                : [],
        ];

        return $this;
    }
}
