<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\EmptyFieldException;

use function trim;

final class Diff
{
    #[Validate]
    public function validate(string $diff): void
    {
        if (trim($diff) === '') {
            throw new EmptyFieldException('diff');
        }
    }
}
