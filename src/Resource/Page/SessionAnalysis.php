<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

use function count;

class SessionAnalysis extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    public function onGet(string $sessionId): static
    {
        $checkpoints = $this->query->sessionAnalysis($sessionId);
        if ($checkpoints === []) {
            $this->code = 404;
            $this->body = [];

            return $this;
        }

        $tagCounts = ['factual' => 0, 'strategic' => 0, 'stylistic' => 0];
        foreach ($checkpoints as $cp) {
            $tag = $cp['tag'];
            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
        }

        $this->body = [
            'sessionId' => $sessionId,
            'taskContext' => $checkpoints[0]['task_context'],
            'checkpointCount' => count($checkpoints),
            'distribution' => $tagCounts,
            'checkpoints' => $checkpoints,
        ];

        return $this;
    }
}
