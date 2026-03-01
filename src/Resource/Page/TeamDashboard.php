<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

class TeamDashboard extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    public function onGet(): static
    {
        $this->body = [
            'members' => $this->query->teamSummary(),
        ];

        return $this;
    }
}
