<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    private SessionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new SessionManager();
    }

    public function testStartSession(): void
    {
        $result = $this->manager->startSession('Salesforce設計');

        $this->assertStringContainsString('Session started', $result);
        $this->assertStringContainsString('ce-', $result);
        $this->assertStringContainsString('Salesforce設計', $result);
        $this->assertNotNull($this->manager->getActiveSessionId());
        $this->assertSame('Salesforce設計', $this->manager->getActiveTaskContext());
    }

    public function testStartSessionWhileActive(): void
    {
        $this->manager->startSession('Task 1');
        $result = $this->manager->startSession('Task 2');

        $this->assertStringContainsString('already active', $result);
    }

    public function testEndSession(): void
    {
        $this->manager->startSession('Task');
        $this->manager->trackCheckpoint('factual');
        $this->manager->trackCheckpoint('strategic');
        $this->manager->trackCheckpoint('factual');

        $result = $this->manager->endSession();

        $this->assertStringContainsString('closed', $result);
        $this->assertStringContainsString('3 checkpoints recorded', $result);
        $this->assertStringContainsString('2 factual', $result);
        $this->assertStringContainsString('1 strategic', $result);
        $this->assertNull($this->manager->getActiveSessionId());
    }

    public function testEndSessionNoCheckpoints(): void
    {
        $this->manager->startSession('Task');
        $result = $this->manager->endSession();

        $this->assertStringContainsString('No checkpoints recorded', $result);
    }

    public function testEndSessionWhenNoActive(): void
    {
        $result = $this->manager->endSession();

        $this->assertSame('No active session to end.', $result);
    }

    public function testResolveSessionIdWithExplicit(): void
    {
        $this->manager->startSession('Task');
        $resolved = $this->manager->resolveSessionId('explicit-id');

        $this->assertSame('explicit-id', $resolved);
    }

    public function testResolveSessionIdFallsBackToActive(): void
    {
        $this->manager->startSession('Task');
        $activeId = $this->manager->getActiveSessionId();

        $resolved = $this->manager->resolveSessionId(null);

        $this->assertSame($activeId, $resolved);
    }

    public function testResolveSessionIdReturnsNullWhenNoSession(): void
    {
        $resolved = $this->manager->resolveSessionId(null);

        $this->assertNull($resolved);
    }

    public function testStartSessionWithCustomUserId(): void
    {
        $this->manager->startSession('Task', 'custom-user');

        $this->assertSame('custom-user', $this->manager->getUserId());
    }

    public function testGetUserIdDefaultsToDefault(): void
    {
        $this->assertSame('default', $this->manager->getUserId());

        $this->manager->startSession('Task');

        $this->assertSame('default', $this->manager->getUserId());
    }

    public function testEndSessionResetsUserId(): void
    {
        $this->manager->startSession('Task', 'custom-user');
        $this->assertSame('custom-user', $this->manager->getUserId());

        $this->manager->endSession();
        $this->assertSame('default', $this->manager->getUserId());
    }

    public function testSessionIdFormat(): void
    {
        $this->manager->startSession('Task');
        $sessionId = $this->manager->getActiveSessionId();

        $this->assertNotNull($sessionId);
        $this->assertMatchesRegularExpression('/^ce-\d{4}-\d{2}-\d{2}-.{8}$/', $sessionId);
    }
}
