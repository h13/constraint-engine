<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Query\CheckpointQueryInterface;

class Checkpoint extends ResourceObject
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
    ) {
    }

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
}
