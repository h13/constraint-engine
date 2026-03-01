<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;

use function array_slice;
use function implode;

final class ImprovementSuggester
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are an AI proposal improvement assistant. You have access to a history of past modifications that humans made to AI proposals.

Given a new AI proposal and task context, along with relevant past modification patterns, suggest specific improvements to the proposal BEFORE the human reviews it.

Focus on:
1. Factual corrections — based on past factual error patterns in similar contexts
2. Strategic adjustments — based on known business preferences
3. Style conformance — based on past stylistic corrections

Respond in Japanese. Be specific and actionable. Format as a numbered list of suggested changes.
If no relevant patterns exist, say so briefly.
PROMPT;

    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly AnthropicClientInterface $client,
    ) {
    }

    /**
     * Suggest improvements to an AI proposal based on past modification patterns.
     *
     * @param string $aiProposal  The AI-generated proposal to improve
     * @param string $taskContext The domain/task being worked on
     *
     * @return string Suggested improvements based on historical patterns
     */
    #[McpTool(name: 'suggest_improvements')]
    public function suggestImprovements(string $aiProposal, string $taskContext): string
    {
        $checkpoints = $this->query->detailList();
        if ($checkpoints === []) {
            return 'No past modification data available yet. Start recording checkpoints to enable pattern-based suggestions.';
        }

        $context = $this->buildContext($aiProposal, $taskContext, $checkpoints);

        try {
            return $this->client->complete(
                self::SYSTEM_PROMPT,
                "Based on past patterns, suggest improvements for this proposal:\n\n{$context}",
                500,
            );
        } catch (RuntimeException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /** @param array<array{tag: string, diff: string, taskContext: string, aiProposal: string, humanFinal: string}> $checkpoints */
    private function buildContext(string $aiProposal, string $taskContext, array $checkpoints): string
    {
        $recent = array_slice($checkpoints, 0, 30);

        $lines = [
            'NEW PROPOSAL:',
            "Task: {$taskContext}",
            "Proposal: {$aiProposal}",
            '',
            'PAST MODIFICATION HISTORY (most recent):',
        ];

        foreach ($recent as $i => $cp) {
            $num = $i + 1;
            $lines[] = "#{$num} [{$cp['tag']}] {$cp['taskContext']}: {$cp['aiProposal']} → {$cp['humanFinal']}";
        }

        return implode("\n", $lines);
    }
}
