<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;

use function count;
use function implode;

final class SessionAnalyzer
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are an AI-human collaboration pattern analyst. Given a list of checkpoints from a session, analyze the patterns and provide actionable insights in Japanese.

Focus on:
1. Which types of modifications are most common and why
2. Patterns in what the human changed (factual errors, strategic decisions, style preferences)
3. Specific recommendations to reduce future modifications

Be concise (3-5 bullet points). Use plain language.
PROMPT;

    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly AnthropicClientInterface $client,
    ) {
    }

    /**
     * Analyze a session's checkpoint patterns using AI.
     *
     * @param string $sessionId The session ID to analyze
     *
     * @return string Natural language analysis of the session's patterns
     */
    #[McpTool(name: 'analyze_session')]
    public function analyzeSession(string $sessionId): string
    {
        $checkpoints = $this->query->sessionAnalysis($sessionId);
        if ($checkpoints === []) {
            return "Error: No checkpoints found for session '{$sessionId}'.";
        }

        $summary = $this->buildSummary($checkpoints);

        try {
            return $this->client->complete(
                self::SYSTEM_PROMPT,
                "Analyze this session's modification patterns:\n\n{$summary}",
                500,
            );
        } catch (RuntimeException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /** @param array<array{tag: string, aiProposal: string, humanFinal: string, diff: string, taskContext: string}> $checkpoints */
    private function buildSummary(array $checkpoints): string
    {
        $lines = ['Session checkpoints (' . count($checkpoints) . ' total):'];
        foreach ($checkpoints as $i => $cp) {
            $num = $i + 1;
            $lines[] = "#{$num} [{$cp['tag']}] {$cp['taskContext']}: {$cp['diff']}";
        }

        return implode("\n", $lines);
    }
}
