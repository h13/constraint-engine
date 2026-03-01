<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Being;

use Aura\Sql\ExtendedPdoInterface;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use RuntimeException;

final readonly class RecordedCheckpoint
{
    public int $id;

    public function __construct(
        #[Input]
        public string $sessionId,
        #[Input]
        public string $taskContext,
        #[Input]
        public string $aiProposal,
        #[Input]
        public string $humanFinal,
        #[Input]
        public string $diff,
        #[Input]
        public string $tag,
        #[Input]
        public string $confidence,
        #[Input]
        public string $userId,
        #[Inject]
        CheckpointCommandInterface $command,
        #[Inject]
        ExtendedPdoInterface $pdo,
    ) {
        $command->add(
            user_id: $userId,
            session_id: $sessionId,
            task_context: $taskContext,
            ai_proposal: $aiProposal,
            human_final: $humanFinal,
            diff: $diff,
            tag: $tag,
            confidence: $confidence,
        );
        $lastId = $pdo->lastInsertId();
        if ($lastId === false || $lastId === '0' || $lastId === '') {
            throw new RuntimeException('Failed to record checkpoint: could not retrieve insert ID');
        }

        $this->id = (int) $lastId;
    }
}
