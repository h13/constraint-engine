<?php

declare(strict_types=1);

namespace ConstraintEngine\App;

use PHPUnit\Framework\TestCase;

class GoNoGoVerdictTest extends TestCase
{
    public function testGoWhenAllTargetsMet(): void
    {
        $this->assertSame('go', GoNoGoVerdict::compute(3, 1, 0));
    }

    public function testGoWhenExceedingTargets(): void
    {
        $this->assertSame('go', GoNoGoVerdict::compute(10, 5, 0));
    }

    public function testNoGoWhenFrictionExceedsLimit(): void
    {
        $this->assertSame('no_go', GoNoGoVerdict::compute(5, 3, 3));
    }

    public function testNoGoFrictionTakesPrecedenceOverGoConditions(): void
    {
        $this->assertSame('no_go', GoNoGoVerdict::compute(3, 1, 3));
    }

    public function testPendingWhenRecallBelowTarget(): void
    {
        $this->assertSame('pending', GoNoGoVerdict::compute(2, 1, 0));
    }

    public function testPendingWhenDiscoveryBelowTarget(): void
    {
        $this->assertSame('pending', GoNoGoVerdict::compute(3, 0, 0));
    }

    public function testPendingWhenAllZero(): void
    {
        $this->assertSame('pending', GoNoGoVerdict::compute(0, 0, 0));
    }

    public function testFrictionAtLimitIsNotNoGo(): void
    {
        $this->assertSame('go', GoNoGoVerdict::compute(3, 1, 2));
    }

    public function testRecallAtExactTargetBoundary(): void
    {
        $this->assertSame('go', GoNoGoVerdict::compute(GoNoGoVerdict::RECALL_TARGET, GoNoGoVerdict::DISCOVERY_TARGET, 0));
    }

    public function testRecallBelowTargetByOne(): void
    {
        $this->assertSame('pending', GoNoGoVerdict::compute(GoNoGoVerdict::RECALL_TARGET - 1, GoNoGoVerdict::DISCOVERY_TARGET, 0));
    }

    public function testFrictionAtExactLimitPlusOne(): void
    {
        $this->assertSame('no_go', GoNoGoVerdict::compute(0, 0, GoNoGoVerdict::FRICTION_LIMIT + 1));
    }

    public function testConstants(): void
    {
        $this->assertSame(3, GoNoGoVerdict::RECALL_TARGET);
        $this->assertSame(1, GoNoGoVerdict::DISCOVERY_TARGET);
        $this->assertSame(2, GoNoGoVerdict::FRICTION_LIMIT);
    }
}
