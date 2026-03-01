<?php

declare(strict_types=1);

namespace ConstraintEngine\App;

final class GoNoGoVerdict
{
    public const int RECALL_TARGET = 3;
    public const int DISCOVERY_TARGET = 1;
    public const int FRICTION_LIMIT = 2;

    public static function compute(int $recall, int $discovery, int $friction): string
    {
        if ($friction > self::FRICTION_LIMIT) {
            return 'no_go';
        }

        if ($recall >= self::RECALL_TARGET && $discovery >= self::DISCOVERY_TARGET) {
            return 'go';
        }

        return 'pending';
    }
}
