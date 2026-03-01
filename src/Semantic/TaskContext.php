<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\EmptyFieldException;

use function trim;

final class TaskContext
{
    #[Validate]
    public function validate(string $taskContext): void
    {
        if (trim($taskContext) === '') {
            throw new EmptyFieldException('taskContext');
        }
    }
}
