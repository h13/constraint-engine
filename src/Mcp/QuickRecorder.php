<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use Mcp\Capability\Attribute\McpTool;
use PDOException;

final class QuickRecorder
{
    public function __construct(
        private readonly DescriptionParser $parser,
        private readonly CheckpointCommandInterface $command,
        private readonly ExtendedPdoInterface $pdo,
        private readonly SessionManager $sessionManager,
    ) {
    }

    /**
     * Quick-record a checkpoint from a single description.
     *
     * Just describe what changed and why — the system extracts the structured data automatically.
     *
     * @param string $description Free-text description of the AI-to-human modification
     *
     * @return string Confirmation message with classification result and checkpoint ID
     */
    #[McpTool(name: 'quick_record')]
    public function quickRecord(string $description): string
    {
        $sessionId = $this->sessionManager->getActiveSessionId();
        if ($sessionId === null) {
            return 'Error: No active session. Use start_session first.';
        }

        $parsed = $this->parser->parse($description);

        if ($parsed['aiProposal'] === '' || $parsed['humanFinal'] === '') {
            return 'Error: Could not extract proposal data from description. Please provide more detail.';
        }

        $taskContext = $parsed['taskContext'];
        $activeContext = $this->sessionManager->getActiveTaskContext();
        if ($taskContext === '' && $activeContext !== null) {
            $taskContext = $activeContext;
        }

        $diff = $parsed['aiProposal'] === $parsed['humanFinal']
            ? '(no changes)'
            : "{$parsed['aiProposal']} → {$parsed['humanFinal']}";

        try {
            $this->command->add(
                user_id: $this->sessionManager->getUserId(),
                session_id: $sessionId,
                task_context: $taskContext,
                ai_proposal: $parsed['aiProposal'],
                human_final: $parsed['humanFinal'],
                diff: $diff,
                tag: $parsed['tag'],
                confidence: $parsed['confidence'],
            );
        } catch (PDOException) {
            return 'Error: Failed to write checkpoint. Please check system logs.';
        }

        $lastId = $this->pdo->lastInsertId();
        if ($lastId === false || $lastId === '0' || $lastId === '') {
            return 'Error: Failed to record checkpoint — database write did not return a valid insert ID.';
        }

        $id = (int) $lastId;
        $this->sessionManager->trackCheckpoint($parsed['tag']);

        return "Checkpoint recorded. Classification: {$parsed['tag']}. ID: {$id}";
    }
}
