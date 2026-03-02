<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    #[Link(rel: 'goCheckpointList', href: '/checkpoints')]
    #[Link(rel: 'goPatternDashboard', href: '/pattern-dashboard')]
    #[Link(rel: 'goSessionList', href: '/sessions')]
    #[Link(rel: 'goTeamDashboard', href: '/team-dashboard')]
    #[Link(rel: 'goGoNoGo', href: '/go-no-go')]
    public function onGet(): static
    {
        $this->body = [
            'name' => 'Constraint Engine',
            'description' => 'AI-human collaboration checkpoint tracking and pattern analysis platform',
        ];

        return $this;
    }
}
