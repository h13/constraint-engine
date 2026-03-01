<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Mcp\Capability\Attribute\McpTool;

use function array_slice;
use function implode;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class ImprovementSuggester
{
    private const string MODEL = 'claude-sonnet-4-5-20250929';
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
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
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
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
        $checkpoints = $this->query->list();
        if ($checkpoints === []) {
            return 'No past modification data available yet. Start recording checkpoints to enable pattern-based suggestions.';
        }

        $context = $this->buildContext($aiProposal, $taskContext, $checkpoints);

        return $this->suggest($context);
    }

    /** @param array<array{tag: string, diff: string, task_context: string, ai_proposal: string, human_final: string}> $checkpoints */
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
            $lines[] = "#{$num} [{$cp['tag']}] {$cp['task_context']}: {$cp['ai_proposal']} → {$cp['human_final']}";
        }

        return implode("\n", $lines);
    }

    private function suggest(string $context): string
    {
        $body = json_encode([
            'model' => self::MODEL,
            'max_tokens' => 500,
            'messages' => [
                ['role' => 'user', 'content' => "Based on past patterns, suggest improvements for this proposal:\n\n{$context}"],
            ],
            'system' => self::SYSTEM_PROMPT,
        ], JSON_THROW_ON_ERROR);

        $request = new Request('POST', self::API_URL, [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ], $body);

        $response = $this->httpClient->send($request);
        $responseBody = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $responseBody['content'][0]['text'] ?? 'No suggestions available.';
    }
}
