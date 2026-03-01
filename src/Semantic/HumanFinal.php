<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\EmptyFieldException;

use function trim;

final class HumanFinal
{
    #[Validate]
    public function validate(string $humanFinal): void
    {
        if (trim($humanFinal) === '') {
            throw new EmptyFieldException('humanFinal');
        }
    }
}
