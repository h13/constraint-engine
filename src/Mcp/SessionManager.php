<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Mcp\Capability\Attribute\McpTool;

use function bin2hex;
use function date;
use function implode;
use function random_bytes;
use function sprintf;

final class SessionManager
{
    private string|null $activeSessionId = null;
    private string|null $activeTaskContext = null;
    private string $userId = 'default';
    private int $checkpointCount = 0;

    /** @var array<string, int> */
    private array $tagCounts = [];

    /**
     * Start a new recording session with a task context.
     *
     * @param string $taskContext Description of the task being worked on
     *
     * @return string Session ID and confirmation
     */
    #[McpTool(name: 'start_session')]
    public function startSession(string $taskContext, string $userId = 'default'): string
    {
        if ($this->activeSessionId !== null) {
            return "Error: Session '{$this->activeSessionId}' is already active. End it first with end_session.";
        }

        $this->activeSessionId = $this->generateSessionId();
        $this->activeTaskContext = $taskContext;
        $this->userId = $userId;
        $this->checkpointCount = 0;
        $this->tagCounts = [];

        return "Session started. ID: {$this->activeSessionId}, Context: {$taskContext}";
    }

    /**
     * End the current recording session and return a summary.
     *
     * @return string Session summary with checkpoint counts and classification distribution
     */
    #[McpTool(name: 'end_session')]
    public function endSession(): string
    {
        if ($this->activeSessionId === null) {
            return 'No active session to end.';
        }

        $sessionId = $this->activeSessionId;
        $count = $this->checkpointCount;
        $summary = $this->formatTagSummary();

        $this->activeSessionId = null;
        $this->activeTaskContext = null;
        $this->checkpointCount = 0;
        $this->tagCounts = [];

        if ($count === 0) {
            return "Session '{$sessionId}' closed. No checkpoints recorded.";
        }

        return "Session '{$sessionId}' closed. {$count} checkpoints recorded ({$summary}).";
    }

    public function getActiveSessionId(): string|null
    {
        return $this->activeSessionId;
    }

    public function getActiveTaskContext(): string|null
    {
        return $this->activeTaskContext;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function trackCheckpoint(string $tag): void
    {
        $this->checkpointCount++;
        $this->tagCounts[$tag] = ($this->tagCounts[$tag] ?? 0) + 1;
    }

    public function resolveSessionId(string|null $sessionId): string|null
    {
        if ($sessionId !== null) {
            return $sessionId;
        }

        return $this->activeSessionId;
    }

    private function generateSessionId(): string
    {
        return sprintf('ce-%s-%s', date('Y-m-d'), bin2hex(random_bytes(4)));
    }

    private function formatTagSummary(): string
    {
        $parts = [];
        foreach ($this->tagCounts as $tag => $count) {
            $parts[] = "{$count} {$tag}";
        }

        return implode(', ', $parts);
    }
}
