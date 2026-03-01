<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Being\RecordedCheckpoint;
use ConstraintEngine\App\Input\CheckpointInput;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

use function assert;

class Checkpoints extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly BecomingInterface $becoming,
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
        $input = new CheckpointInput(
            sessionId: $sessionId,
            taskContext: $taskContext,
            aiProposal: $aiProposal,
            humanFinal: $humanFinal,
            diff: $diff,
            tag: $tag,
            confidence: $confidence,
        );

        try {
            $result = ($this->becoming)($input);
            assert($result instanceof RecordedCheckpoint);
        } catch (SemanticVariableException $e) {
            $this->code = 422;
            $this->body = ['errors' => $e->getErrors()->getMessages('en')];

            return $this;
        }

        $this->code = Code::CREATED;
        $this->headers['Location'] = "/checkpoints/{$result->id}";
        $this->body = ['id' => $result->id];

        return $this;
    }
}
