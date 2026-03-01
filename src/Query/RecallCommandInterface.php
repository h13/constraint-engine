<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface RecallCommandInterface
{
    #[DbQuery('recall_add')]
    public function add(int $checkpoint_id, string $type, string $note): void;
}
