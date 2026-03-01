<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;

use function array_slice;
use function count;
use function implode;

final class TemplateSuggester
{
    private const int MIN_CHECKPOINTS = 3;
    private const int MAX_TOKENS = 800;
    private const int RECENT_LIMIT = 50;
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are a style template generator. You analyze patterns in stylistic corrections that humans made to AI output.

From the provided stylistic modification history, extract recurring patterns and generate reusable style templates/rules.

Output format (in Japanese):
1. Template name (short, descriptive)
2. Rule description (what to do / what to avoid)
3. Before → After example (from actual data)

Group related corrections into single templates. Focus on patterns that appear multiple times.
If there are too few patterns, say so and suggest recording more stylistic checkpoints.
PROMPT;

    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly AnthropicClientInterface $client,
    ) {
    }

    /**
     * Generate shared style templates from past stylistic corrections.
     *
     * Analyzes stylistic checkpoint patterns and produces reusable templates for team-wide style consistency.
     *
     * @return string Generated templates or guidance message
     */
    #[McpTool(name: 'suggest_template')]
    public function suggestTemplate(): string
    {
        $checkpoints = $this->query->stylisticCheckpoints();
        $total = count($checkpoints);

        if ($total < self::MIN_CHECKPOINTS) {
            return "Insufficient stylistic data: {$total} stylistic checkpoints found (minimum " . self::MIN_CHECKPOINTS . ' required). Record more stylistic corrections to generate shared templates.';
        }

        $context = $this->buildContext($checkpoints);

        try {
            $templates = $this->client->complete(
                self::SYSTEM_PROMPT,
                "Generate shared style templates from these {$total} stylistic corrections:\n\n{$context}",
                self::MAX_TOKENS,
            );
        } catch (RuntimeException) {
            return 'Error: Failed to generate templates. Please check system logs.';
        }

        return "=== Shared Style Templates ===\n(Based on {$total} stylistic corrections)\n\n{$templates}";
    }

    /** @param array<array{taskContext: string, aiProposal: string, humanFinal: string, diff: string}> $checkpoints */
    private function buildContext(array $checkpoints): string
    {
        $recent = array_slice($checkpoints, 0, self::RECENT_LIMIT);

        $lines = ['STYLISTIC MODIFICATION HISTORY:'];
        foreach ($recent as $i => $cp) {
            $num = $i + 1;
            $lines[] = "#{$num} [{$cp['taskContext']}] {$cp['aiProposal']} → {$cp['humanFinal']}";
        }

        return implode("\n", $lines);
    }
}
