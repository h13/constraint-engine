<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;

use function array_slice;
use function implode;
use function number_format;

final class InsightGenerator
{
    private const int MIN_CHECKPOINTS = 10;
    private const int MAX_TOKENS = 600;
    private const int RECENT_LIMIT = 20;
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
        private readonly AnthropicClientInterface $client,
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

        try {
            return $this->client->complete(
                self::SYSTEM_PROMPT,
                "Analyze these checkpoint patterns and generate insights:\n\n{$dataContext}",
                self::MAX_TOKENS,
            );
        } catch (RuntimeException $e) {
            return 'Error: ' . $e->getMessage();
        }
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

        $pct = static fn (int $part): string => $total > 0 ? number_format($part / $total * 100, 1) : '0.0';
        $lines = [
            "Overall: {$total} checkpoints",
            "Factual: {$factual} (" . $pct($factual) . '%)',
            "Strategic: {$strategic} (" . $pct($strategic) . '%)',
            "Stylistic: {$stylistic} (" . $pct($stylistic) . '%)',
            '',
            'Recent checkpoints (last 20):',
        ];

        $recent = array_slice($checkpoints, 0, self::RECENT_LIMIT);
        foreach ($recent as $i => $cp) {
            $num = $i + 1;
            $lines[] = "#{$num} [{$cp['tag']}] {$cp['taskContext']}: {$cp['diff']}";
        }

        return implode("\n", $lines);
    }
}
