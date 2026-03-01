<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use Mcp\Capability\Attribute\McpTool;
use PDOException;

use function trim;

final class CheckpointRecorder
{
    public function __construct(
        private readonly DiffClassifier $classifier,
        private readonly CheckpointCommandInterface $command,
        private readonly ExtendedPdoInterface $pdo,
        private readonly SessionManager $sessionManager,
    ) {
    }

    /**
     * Record a checkpoint capturing how a human modified an AI proposal.
     *
     * @param string $aiProposal  The original AI-generated proposal
     * @param string $humanFinal  The human's final version after modifications
     * @param string $taskContext Description of the task being worked on
     * @param string $sessionId   Unique session identifier (optional if session is active)
     *
     * @return string Confirmation message with classification result and checkpoint ID
     */
    #[McpTool(name: 'record_checkpoint')]
    public function recordCheckpoint(
        string $aiProposal,
        string $humanFinal,
        string $taskContext,
        string $sessionId = '',
    ): string {
        if (trim($aiProposal) === '') {
            return 'Error: aiProposal cannot be empty.';
        }

        if (trim($humanFinal) === '') {
            return 'Error: humanFinal cannot be empty.';
        }

        if (trim($taskContext) === '') {
            return 'Error: taskContext cannot be empty.';
        }

        $resolvedSessionId = $this->sessionManager->resolveSessionId(
            $sessionId !== '' ? $sessionId : null,
        );

        if ($resolvedSessionId === null) {
            return 'Error: No sessionId provided and no active session. Use start_session first or provide sessionId.';
        }

        $diff = $this->computeDiff($aiProposal, $humanFinal);
        $classification = $this->classifier->classify($diff);

        try {
            $this->command->add(
                user_id: $this->sessionManager->getUserId(),
                session_id: $resolvedSessionId,
                task_context: $taskContext,
                ai_proposal: $aiProposal,
                human_final: $humanFinal,
                diff: $diff,
                tag: $classification['tag'],
                confidence: $classification['confidence'],
            );
        } catch (PDOException $e) {
            return 'Error: Failed to write checkpoint — ' . $e->getMessage();
        }

        $lastId = $this->pdo->lastInsertId();
        if ($lastId === false || $lastId === '0' || $lastId === '') {
            return 'Error: Failed to record checkpoint — database write did not return a valid insert ID.';
        }

        $id = (int) $lastId;
        $this->sessionManager->trackCheckpoint($classification['tag']);

        return "Checkpoint recorded. Classification: {$classification['tag']}. ID: {$id}";
    }

    private function computeDiff(string $aiProposal, string $humanFinal): string
    {
        if ($aiProposal === $humanFinal) {
            return '(no changes)';
        }

        return "{$aiProposal} → {$humanFinal}";
    }
}
