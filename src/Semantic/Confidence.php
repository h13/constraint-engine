<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\InvalidConfidenceException;

use function in_array;

final class Confidence
{
    private const array VALID = ['estimated', 'stated'];

    #[Validate]
    public function validate(string $confidence): void
    {
        if (! in_array($confidence, self::VALID, true)) {
            throw new InvalidConfidenceException($confidence);
        }
    }
}
