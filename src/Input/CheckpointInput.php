<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Input;

use Be\Framework\Attribute\Be;
use ConstraintEngine\App\Being\RecordedCheckpoint;

#[Be(RecordedCheckpoint::class)]
final readonly class CheckpointInput
{
    public function __construct(
        public string $sessionId,
        public string $taskContext,
        public string $aiProposal,
        public string $humanFinal,
        public string $diff,
        public string $tag,
        public string $confidence,
        public string $userId = 'default',
    ) {
    }
}
