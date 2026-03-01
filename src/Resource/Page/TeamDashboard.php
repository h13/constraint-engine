<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

class TeamDashboard extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    #[Link(rel: 'goCheckpointList', href: '/checkpoints')]
    public function onGet(): static
    {
        $this->body = $this->query->teamSummary();

        return $this;
    }
}
