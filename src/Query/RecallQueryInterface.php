<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface RecallQueryInterface
{
    /** @return array{recallCount: int, discoveryCount: int, frictionCount: int}|null */
    #[DbQuery('recall_summary', type: 'row')]
    public function summary(): array|null;
}
