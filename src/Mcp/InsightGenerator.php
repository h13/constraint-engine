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
use function number_format;

use const JSON_THROW_ON_ERROR;

final class InsightGenerator
{
    private const string MODEL = 'claude-sonnet-4-5-20250929';
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const int MIN_CHECKPOINTS = 10;
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are an AI-human collaboration pattern analyst. Given checkpoint statistics and recent modification history, generate actionable insights in Japanese.

Analyze:
1. Distribution balance — is one type dominating? What does that mean?
2. Trends — are factual errors decreasing (learning effect)?
3. Actionable recommendations — specific steps to improve collaboration quality
4. Efficiency opportunities — which modification types can be reduced?

Be concise (3-5 bullet points). Use data to support each insight. End with one specific recommendation.
PROMPT;

    public function __construct(
        private readonly CheckpointQueryInterface $query,
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
    ) {
    }

    /**
     * Generate AI-powered insights from checkpoint patterns.
     *
     * Requires at least 10 checkpoints for meaningful analysis.
     *
     * @return string Natural language insights with actionable recommendations
     */
    #[McpTool(name: 'generate_insights')]
    public function generateInsights(): string
    {
        $summary = $this->query->summary();
        if ($summary === null || (int) $summary['totalCheckpoints'] < self::MIN_CHECKPOINTS) {
            $current = $summary !== null ? (int) $summary['totalCheckpoints'] : 0;

            return "Insufficient data: {$current} checkpoints recorded (minimum " . self::MIN_CHECKPOINTS . ' required). Keep recording to unlock insights.';
        }

        $checkpoints = $this->query->detailList();
        $dataContext = $this->buildContext($summary, $checkpoints);

        return $this->analyze($dataContext);
    }

    /**
     * @param array{totalCheckpoints: int, factualCount: int, strategicCount: int, stylisticCount: int} $summary
     * @param array<array{tag: string, diff: string, taskContext: string, sessionId: string}>           $checkpoints
     */
    private function buildContext(array $summary, array $checkpoints): string
    {
        $total = (int) $summary['totalCheckpoints'];
        $factual = (int) $summary['factualCount'];
        $strategic = (int) $summary['strategicCount'];
        $stylistic = (int) $summary['stylisticCount'];

        $lines = [
            "Overall: {$total} checkpoints",
            "Factual: {$factual} (" . number_format($factual / $total * 100, 1) . '%)',
            "Strategic: {$strategic} (" . number_format($strategic / $total * 100, 1) . '%)',
            "Stylistic: {$stylistic} (" . number_format($stylistic / $total * 100, 1) . '%)',
            '',
            'Recent checkpoints (last 20):',
        ];

        $recent = array_slice($checkpoints, 0, 20);
        foreach ($recent as $i => $cp) {
            $num = $i + 1;
            $lines[] = "#{$num} [{$cp['tag']}] {$cp['taskContext']}: {$cp['diff']}";
        }

        return implode("\n", $lines);
    }

    private function analyze(string $context): string
    {
        $body = json_encode([
            'model' => self::MODEL,
            'max_tokens' => 600,
            'messages' => [
                ['role' => 'user', 'content' => "Analyze these checkpoint patterns and generate insights:\n\n{$context}"],
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

        return $responseBody['content'][0]['text'] ?? 'Insights unavailable.';
    }
}
