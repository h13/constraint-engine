<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use ConstraintEngine\App\Query\RecallCommandInterface;
use ConstraintEngine\App\Query\RecallQueryInterface;
use Mcp\Capability\Attribute\McpTool;

use function implode;
use function strtoupper;
use function ucfirst;

final class RecallTracker
{
    private const int RECALL_TARGET = 3;
    private const int DISCOVERY_TARGET = 1;
    private const int FRICTION_LIMIT = 2;

    public function __construct(
        private readonly RecallCommandInterface $command,
        private readonly RecallQueryInterface $query,
        private readonly CheckpointQueryInterface $checkpointQuery,
    ) {
    }

    /**
     * Record that a checkpoint was recalled and used in a business decision.
     *
     * @param int    $checkpointId The checkpoint ID that was useful
     * @param string $note         Optional note about how it was used
     *
     * @return string Confirmation message
     */
    #[McpTool(name: 'record_recall')]
    public function recordRecall(int $checkpointId, string $note = ''): string
    {
        return $this->record($checkpointId, 'recall', $note);
    }

    /**
     * Record that an unexpected trend was discovered in the pattern data.
     *
     * @param int    $checkpointId The related checkpoint ID
     * @param string $note         Description of the discovery
     *
     * @return string Confirmation message
     */
    #[McpTool(name: 'record_discovery')]
    public function recordDiscovery(int $checkpointId, string $note = ''): string
    {
        return $this->record($checkpointId, 'discovery', $note);
    }

    /**
     * Record that the checkpoint process felt obstructive.
     *
     * @param int    $checkpointId The related checkpoint ID
     * @param string $note         Description of the friction
     *
     * @return string Confirmation message
     */
    #[McpTool(name: 'record_friction')]
    public function recordFriction(int $checkpointId, string $note = ''): string
    {
        return $this->record($checkpointId, 'friction', $note);
    }

    /**
     * Show the current Go/No-Go status based on recall, discovery, and friction metrics.
     *
     * @return string Go/No-Go status summary
     */
    #[McpTool(name: 'show_go_no_go')]
    public function showGoNoGo(): string
    {
        $summary = $this->query->summary();
        $recall = $summary !== null ? (int) $summary['recallCount'] : 0;
        $discovery = $summary !== null ? (int) $summary['discoveryCount'] : 0;
        $friction = $summary !== null ? (int) $summary['frictionCount'] : 0;
        $verdict = $this->computeVerdict($recall, $discovery, $friction);

        $lines = [
            'Go/No-Go Status',
            '---',
            "Recall:    {$recall} / " . self::RECALL_TARGET . ' (checkpoint reuse in decisions)',
            "Discovery: {$discovery} / " . self::DISCOVERY_TARGET . ' (unexpected trend findings)',
            "Friction:  {$friction} / " . self::FRICTION_LIMIT . ' limit (obstruction events)',
            '---',
            'Verdict: ' . strtoupper($verdict),
        ];

        return implode("\n", $lines);
    }

    private function record(int $checkpointId, string $type, string $note): string
    {
        $item = $this->checkpointQuery->item($checkpointId);
        if ($item === null) {
            return "Error: Checkpoint #{$checkpointId} not found.";
        }

        $this->command->add($checkpointId, $type, $note);

        return ucfirst($type) . " recorded for checkpoint #{$checkpointId}.";
    }

    private function computeVerdict(int $recall, int $discovery, int $friction): string
    {
        if ($friction > self::FRICTION_LIMIT) {
            return 'no_go';
        }

        if ($recall >= self::RECALL_TARGET && $discovery >= self::DISCOVERY_TARGET) {
            return 'go';
        }

        return 'pending';
    }
}
