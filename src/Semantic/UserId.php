<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\EmptyFieldException;

use function trim;

final class UserId
{
    #[Validate]
    public function validate(string $userId): void
    {
        if (trim($userId) === '') {
            throw new EmptyFieldException('userId');
        }
    }
}
