<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    #[Link(rel: 'checkpointList', href: '/checkpoints')]
    #[Link(rel: 'patternDashboard', href: '/pattern-dashboard')]
    #[Link(rel: 'sessionList', href: '/sessions')]
    #[Link(rel: 'teamDashboard', href: '/team-dashboard')]
    #[Link(rel: 'goNoGo', href: '/go-no-go')]
    public function onGet(string $name = 'BEAR.Sunday'): static
    {
        $this->body = [
            'greeting' => 'Hello ' . $name,
        ];

        return $this;
    }
}
