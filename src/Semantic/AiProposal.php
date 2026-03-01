<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\Attribute\Validate;
use ConstraintEngine\App\Exception\EmptyFieldException;

use function trim;

final class AiProposal
{
    #[Validate]
    public function validate(string $aiProposal): void
    {
        if (trim($aiProposal) === '') {
            throw new EmptyFieldException('aiProposal');
        }
    }
}
