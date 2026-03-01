<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface CheckpointCommandInterface
{
    #[DbQuery('checkpoint_add')]
    public function add(
        string $session_id,
        string $task_context,
        string $ai_proposal,
        string $human_final,
        string $diff,
        string $tag,
        string $confidence,
    ): void;
}
