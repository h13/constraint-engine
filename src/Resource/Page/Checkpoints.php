<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

class Checkpoints extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly CheckpointCommandInterface $command,
        private readonly ExtendedPdoInterface $pdo,
    ) {
    }

    public function onGet(): static
    {
        $this->body = $this->query->list();

        return $this;
    }

    public function onPost(
        string $sessionId,
        string $taskContext,
        string $aiProposal,
        string $humanFinal,
        string $diff,
        string $tag,
        string $confidence,
    ): static {
        $this->command->add(
            session_id: $sessionId,
            task_context: $taskContext,
            ai_proposal: $aiProposal,
            human_final: $humanFinal,
            diff: $diff,
            tag: $tag,
            confidence: $confidence,
        );
        $id = $this->pdo->lastInsertId();
        $this->code = Code::CREATED;
        $this->headers['Location'] = "/checkpoints/{$id}";
        $this->body = ['id' => (int) $id];

        return $this;
    }
}
