<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid tag: "{value}". Must be one of: factual, strategic, stylistic',
    'ja' => '無効なタグ: "{value}"。factual, strategic, stylistic のいずれかを指定してください',
])]
final class InvalidTagException extends DomainException
{
    public function __construct(
        public readonly string $value,
    ) {
        parent::__construct("Invalid tag: \"{$value}\"");
    }
}
