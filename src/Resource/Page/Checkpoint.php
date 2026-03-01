<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Exception\InvalidTagException;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use ConstraintEngine\App\Query\CheckpointQueryInterface;
use ConstraintEngine\App\Semantic\Tag;
use PDOException;

class Checkpoint extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly CheckpointCommandInterface $command,
        private readonly Tag $tagValidator,
    ) {
    }

    #[Link(rel: 'goCheckpointList', href: '/checkpoints')]
    #[Link(rel: 'doUpdateCheckpoint', href: '/checkpoints/{checkpointId}')]
    public function onGet(int $id): static
    {
        $item = $this->query->item($id);
        if ($item === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => "Checkpoint #{$id} not found"];

            return $this;
        }

        $this->body = $item;

        return $this;
    }

    #[Link(rel: 'goCheckpointList', href: '/checkpoints')]
    public function onPut(int $id, string $tag): static
    {
        $item = $this->query->item($id);
        if ($item === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => "Checkpoint #{$id} not found"];

            return $this;
        }

        try {
            $this->tagValidator->validate($tag);
        } catch (InvalidTagException $e) {
            $this->code = 422;
            $this->body = ['errors' => [$e->getMessage()]];

            return $this;
        }

        try {
            $this->command->updateTag($id, $tag);
        } catch (PDOException) {
            $this->code = 500;
            $this->body = ['error' => 'An internal error occurred while updating the checkpoint.'];

            return $this;
        }

        $this->body = $this->query->item($id);

        return $this;
    }
}
