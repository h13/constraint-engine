<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Mcp\Capability\Attribute\McpTool;

use function array_slice;
use function count;
use function implode;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class TemplateSuggester
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const int MIN_CHECKPOINTS = 3;
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
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model,
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
        if ($this->apiKey === '') {
            return 'Error: ANTHROPIC_API_KEY is not configured. Set the environment variable to use template suggestions.';
        }

        $checkpoints = $this->query->stylisticCheckpoints();
        $total = count($checkpoints);

        if ($total < self::MIN_CHECKPOINTS) {
            return "Insufficient stylistic data: {$total} stylistic checkpoints found (minimum " . self::MIN_CHECKPOINTS . ' required). Record more stylistic corrections to generate shared templates.';
        }

        $context = $this->buildContext($checkpoints);

        return $this->generate($context, $total);
    }

    /** @param array<array{taskContext: string, aiProposal: string, humanFinal: string, diff: string}> $checkpoints */
    private function buildContext(array $checkpoints): string
    {
        $recent = array_slice($checkpoints, 0, 50);

        $lines = ['STYLISTIC MODIFICATION HISTORY:'];
        foreach ($recent as $i => $cp) {
            $num = $i + 1;
            $lines[] = "#{$num} [{$cp['taskContext']}] {$cp['aiProposal']} → {$cp['humanFinal']}";
        }

        return implode("\n", $lines);
    }

    private function generate(string $context, int $total): string
    {
        $body = json_encode([
            'model' => $this->model,
            'max_tokens' => 800,
            'messages' => [
                ['role' => 'user', 'content' => "Generate shared style templates from these {$total} stylistic corrections:\n\n{$context}"],
            ],
            'system' => self::SYSTEM_PROMPT,
        ], JSON_THROW_ON_ERROR);

        $request = new Request('POST', self::API_URL, [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ], $body);

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $e) {
            return 'Error: API request failed — ' . $e->getMessage();
        }

        if ($response->getStatusCode() !== 200) {
            return 'Error: Anthropic API returned HTTP ' . $response->getStatusCode();
        }

        $responseBody = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $templates = $responseBody['content'][0]['text'] ?? 'No templates could be generated.';

        return "=== Shared Style Templates ===\n(Based on {$total} stylistic corrections)\n\n{$templates}";
    }
}
