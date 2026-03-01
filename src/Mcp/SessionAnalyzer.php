<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Mcp\Capability\Attribute\McpTool;

use function count;
use function implode;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class SessionAnalyzer
{
    private const string MODEL = 'claude-sonnet-4-5-20250929';
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
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
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
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
        if ($this->apiKey === '') {
            return 'Error: ANTHROPIC_API_KEY is not configured. Set the environment variable to use analysis features.';
        }

        $checkpoints = $this->query->sessionAnalysis($sessionId);
        if ($checkpoints === []) {
            return "Error: No checkpoints found for session '{$sessionId}'.";
        }

        $summary = $this->buildSummary($checkpoints);

        return $this->analyze($summary);
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

    private function analyze(string $summary): string
    {
        $body = json_encode([
            'model' => self::MODEL,
            'max_tokens' => 500,
            'messages' => [
                ['role' => 'user', 'content' => "Analyze this session's modification patterns:\n\n{$summary}"],
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

        return $responseBody['content'][0]['text'] ?? 'Analysis unavailable.';
    }
}
