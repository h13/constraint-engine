<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\DateHelper;
use ConstraintEngine\App\Query\CheckpointQueryInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Ray\Di\Di\Named;

use function count;
use function date;
use function implode;
use function number_format;
use function strtotime;
use function ucfirst;

final class PatternViewer
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
        #[Named('api_base_url')]
        private readonly string $apiBaseUrl,
    ) {
    }

    /**
     * Show the pattern dashboard with classification distribution summary.
     *
     * @return string Summary text of checkpoint classification distribution
     */
    #[McpTool(name: 'show_pattern')]
    public function showPattern(): string
    {
        $summary = $this->query->summary();
        if ($summary === null || (int) $summary['totalCheckpoints'] === 0) {
            return 'No checkpoints recorded yet.';
        }

        $total = (int) $summary['totalCheckpoints'];
        $factual = (int) $summary['factualCount'];
        $strategic = (int) $summary['strategicCount'];
        $stylistic = (int) $summary['stylisticCount'];

        $lines = [
            "Pattern Dashboard ({$total} checkpoints)",
            '---',
            "Factual:   {$factual} (" . $this->percentage($factual, $total) . '%)',
            "Strategic: {$strategic} (" . $this->percentage($strategic, $total) . '%)',
            "Stylistic: {$stylistic} (" . $this->percentage($stylistic, $total) . '%)',
        ];

        $distribution = $this->query->tagDistribution();
        if ($distribution !== []) {
            $lines[] = '';
            $lines[] = 'Distribution:';
            foreach ($distribution as $row) {
                $lines[] = "  {$row['tag']}: {$row['count']}";
            }
        }

        $lines[] = '';
        $lines[] = "Dashboard: {$this->apiBaseUrl}/pattern-dashboard";

        return implode("\n", $lines);
    }

    /**
     * Compare classification patterns between two time periods.
     *
     * @param string $currentStart  Start of current period (YYYY-MM-DD)
     * @param string $currentEnd    End of current period (YYYY-MM-DD)
     * @param string $previousStart Start of previous period (YYYY-MM-DD)
     * @param string $previousEnd   End of previous period (YYYY-MM-DD)
     *
     * @return string Comparison summary with change rates
     */
    #[McpTool(name: 'compare_periods')]
    public function comparePeriods(
        string $currentStart = '',
        string $currentEnd = '',
        string $previousStart = '',
        string $previousEnd = '',
    ): string {
        if ($currentStart === '' || $currentEnd === '' || $previousStart === '' || $previousEnd === '') {
            $currentStart = date('Y-m-d', strtotime('-7 days'));
            $currentEnd = date('Y-m-d');
            $previousStart = date('Y-m-d', strtotime('-14 days'));
            $previousEnd = date('Y-m-d', strtotime('-8 days'));
        }

        try {
            $currentEndExcl = DateHelper::nextDay($currentEnd);
            $previousEndExcl = DateHelper::nextDay($previousEnd);
        } catch (InvalidArgumentException) {
            return 'Error: Invalid date format. Use YYYY-MM-DD.';
        }

        $current = $this->query->periodSummary($currentStart, $currentEndExcl);
        $previous = $this->query->periodSummary($previousStart, $previousEndExcl);

        $curTotal = $current !== null ? (int) $current['totalCheckpoints'] : 0;
        $prevTotal = $previous !== null ? (int) $previous['totalCheckpoints'] : 0;

        if ($curTotal === 0 && $prevTotal === 0) {
            return 'No checkpoints in either period.';
        }

        $lines = [
            'Period Comparison',
            '---',
            "Current:  {$currentStart} ~ {$currentEnd} ({$curTotal} checkpoints)",
            "Previous: {$previousStart} ~ {$previousEnd} ({$prevTotal} checkpoints)",
            '---',
        ];

        foreach (['factual', 'strategic', 'stylistic'] as $tag) {
            $key = $tag . 'Count';
            $curCount = $current !== null ? (int) $current[$key] : 0;
            $prevCount = $previous !== null ? (int) $previous[$key] : 0;
            $curPct = $curTotal > 0 ? $curCount / $curTotal * 100 : 0;
            $prevPct = $prevTotal > 0 ? $prevCount / $prevTotal * 100 : 0;
            $change = $this->formatChange($prevPct, $curPct);
            $tagLabel = ucfirst($tag);
            $lines[] = "{$tagLabel}: {$curCount} (" . number_format($curPct, 1) . "%) {$change}";
        }

        $lines[] = '';
        $lines[] = "Dashboard: {$this->apiBaseUrl}/pattern-dashboard"
            . "?periodStart={$currentStart}&periodEnd={$currentEnd}"
            . "&compareStart={$previousStart}&compareEnd={$previousEnd}";

        return implode("\n", $lines);
    }

    /**
     * Show factual correction rate trend to measure learning improvement.
     *
     * @param string $periodStart Start of analysis period (YYYY-MM-DD)
     * @param string $periodEnd   End of analysis period (YYYY-MM-DD)
     *
     * @return string Factual rate trend with improvement assessment
     */
    #[McpTool(name: 'show_improvement_rate')]
    public function showImprovementRate(
        string $periodStart = '',
        string $periodEnd = '',
    ): string {
        if ($periodStart === '' || $periodEnd === '') {
            $periodStart = date('Y-m-d', strtotime('-30 days'));
            $periodEnd = date('Y-m-d');
        }

        try {
            $endExcl = DateHelper::nextDay($periodEnd);
        } catch (InvalidArgumentException) {
            return 'Error: Invalid date format. Use YYYY-MM-DD.';
        }

        $rates = $this->query->factualRate($periodStart, $endExcl);
        if ($rates === []) {
            return "No checkpoints found in period {$periodStart} ~ {$periodEnd}.";
        }

        $lines = [
            "Factual Correction Rate ({$periodStart} ~ {$periodEnd})",
            '---',
        ];

        $firstRate = null;
        $lastRate = null;
        foreach ($rates as $row) {
            $rate = (float) $row['factualRate'];
            $lines[] = "{$row['date']}: {$row['total']} checkpoints, factual rate " . number_format($rate, 1) . '%';
            if ($firstRate === null) {
                $firstRate = $rate;
            }

            $lastRate = $rate;
        }

        if (count($rates) >= 2) {
            $rateChange = (float) $lastRate - $firstRate;
            $lines[] = '---';
            if ($rateChange < 0) {
                $lines[] = 'Improvement: factual rate decreased by ' . number_format(-$rateChange, 1) . 'pp (learning effect detected)';
            } elseif ($rateChange > 0) {
                $lines[] = 'Note: factual rate increased by ' . number_format($rateChange, 1) . 'pp (may need attention)';
            } else {
                $lines[] = 'Factual rate stable.';
            }
        }

        return implode("\n", $lines);
    }

    private function percentage(int $part, int $total): string
    {
        if ($total === 0) {
            return '0.0';
        }

        return number_format($part / $total * 100, 1);
    }

    private function formatChange(float $previous, float $current): string
    {
        if ($previous === 0.0 && $current === 0.0) {
            return '(-)';
        }

        if ($previous === 0.0) {
            return '(new)';
        }

        $diff = $current - $previous;
        $sign = $diff >= 0 ? '+' : '';

        return '(' . $sign . number_format($diff, 1) . 'pp)';
    }
}
