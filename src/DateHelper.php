<?php

declare(strict_types=1);

namespace ConstraintEngine\App;

use InvalidArgumentException;

use function date;
use function strtotime;

final class DateHelper
{
    /**
     * Return the next calendar day (exclusive end date for SQL queries).
     *
     * @throws InvalidArgumentException When the date string cannot be parsed.
     */
    public static function nextDay(string $date): string
    {
        $ts = strtotime($date . ' +1 day');
        if ($ts === false) {
            throw new InvalidArgumentException("Invalid date: {$date}");
        }

        return date('Y-m-d', $ts);
    }
}
