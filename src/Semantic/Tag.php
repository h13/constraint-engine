<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\InvalidTagException;

use function in_array;

final class Tag
{
    public const array VALID = ['factual', 'strategic', 'stylistic'];

    #[Validate]
    public function validate(string $tag): void
    {
        if (! in_array($tag, self::VALID, true)) {
            throw new InvalidTagException($tag);
        }
    }
}
