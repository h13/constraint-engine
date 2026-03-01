<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

class Sessions extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    #[Link(rel: 'goSessionAnalysis', href: '/sessions/{sessionId}/analysis')]
    public function onGet(): static
    {
        $this->body = $this->query->sessionList();

        return $this;
    }
}
