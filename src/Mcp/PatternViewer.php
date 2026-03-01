<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use Mcp\Capability\Attribute\McpTool;

use function implode;
use function number_format;

final class PatternViewer
{
    public function __construct(
        private readonly CheckpointQueryInterface $query,
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
        if ($summary === null || (int) $summary['total'] === 0) {
            return 'No checkpoints recorded yet.';
        }

        $total = (int) $summary['total'];
        $factual = (int) $summary['factual_count'];
        $strategic = (int) $summary['strategic_count'];
        $stylistic = (int) $summary['stylistic_count'];

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

        return implode("\n", $lines);
    }

    private function percentage(int $part, int $total): string
    {
        return number_format($part / $total * 100, 1);
    }
}
