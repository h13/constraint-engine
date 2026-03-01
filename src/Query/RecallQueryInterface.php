<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface RecallQueryInterface
{
    /** @return array{recall_count: int, discovery_count: int, friction_count: int}|null */
    #[DbQuery('recall_summary', type: 'row')]
    public function summary(): array|null;
}
