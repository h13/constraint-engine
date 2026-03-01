<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => '"{field}" must not be empty',
    'ja' => '"{field}" は空にできません',
])]
final class EmptyFieldException extends DomainException
{
    public function __construct(
        public readonly string $field,
    ) {
        parent::__construct("\"{$field}\" must not be empty");
    }
}
