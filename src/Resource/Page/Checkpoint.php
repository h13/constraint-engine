<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

use function in_array;

class Checkpoint extends ResourceObject
{
    private const array VALID_TAGS = ['factual', 'strategic', 'stylistic'];

    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly CheckpointCommandInterface $command,
    ) {
    }

    #[Link(rel: 'checkpointList', href: '/checkpoints')]
    #[Link(rel: 'doUpdateCheckpoint', href: '/checkpoints/{checkpointId}')]
    public function onGet(int $id): static
    {
        $item = $this->query->item($id);
        if ($item === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = [];

            return $this;
        }

        $this->body = $item;

        return $this;
    }

    #[Link(rel: 'checkpointList', href: '/checkpoints')]
    public function onPut(int $id, string $tag): static
    {
        $item = $this->query->item($id);
        if ($item === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = [];

            return $this;
        }

        if (! in_array($tag, self::VALID_TAGS, true)) {
            $this->code = 422;
            $this->body = ['errors' => ["Invalid tag: \"{$tag}\". Must be one of: factual, strategic, stylistic"]];

            return $this;
        }

        $this->command->updateTag($id, $tag);
        $this->body = $this->query->item($id);

        return $this;
    }
}
