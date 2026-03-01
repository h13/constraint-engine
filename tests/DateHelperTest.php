<?php

declare(strict_types=1);

namespace ConstraintEngine\App;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    public function testNextDay(): void
    {
        $this->assertSame('2026-01-02', DateHelper::nextDay('2026-01-01'));
    }

    public function testNextDayMonthBoundary(): void
    {
        $this->assertSame('2026-02-01', DateHelper::nextDay('2026-01-31'));
    }

    public function testNextDayYearBoundary(): void
    {
        $this->assertSame('2026-01-01', DateHelper::nextDay('2025-12-31'));
    }

    public function testNextDayLeapYear(): void
    {
        $this->assertSame('2024-02-29', DateHelper::nextDay('2024-02-28'));
    }

    public function testNextDayThrowsOnGarbage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date');
        DateHelper::nextDay('not-a-date');
    }
}
