<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid confidence: "{value}". Must be one of: estimated, stated',
    'ja' => '無効な確信度: "{value}"。estimated, stated のいずれかを指定してください',
])]
final class InvalidConfidenceException extends DomainException
{
    public function __construct(
        public readonly string $value,
    ) {
        parent::__construct("Invalid confidence: \"{$value}\"");
    }
}
