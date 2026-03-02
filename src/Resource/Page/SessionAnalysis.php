<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

use function count;

class SessionAnalysis extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

    #[Link(rel: 'goCheckpointList', href: '/checkpoints{?sessionId}')]
    public function onGet(string $sessionId): static
    {
        $summary = $this->query->sessionAnalysisSummary($sessionId);
        if ($summary === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => "Session '{$sessionId}' not found"];

            return $this;
        }

        $checkpoints = $this->query->sessionAnalysis($sessionId);
        $this->body = [
            'sessionId' => $summary['sessionId'],
            'taskContext' => $summary['taskContext'],
            'checkpointCount' => count($checkpoints),
            'factualCount' => (int) $summary['factualCount'],
            'strategicCount' => (int) $summary['strategicCount'],
            'stylisticCount' => (int) $summary['stylisticCount'],
            'checkpoints' => $checkpoints,
        ];

        return $this;
    }
}
