<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use PDOException;
use PHPUnit\Framework\TestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class CheckpointRecorderTest extends TestCase
{
    private function createClassifier(string $tag, string $confidence = 'estimated'): DiffClassifier
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn(
            json_encode(['tag' => $tag, 'confidence' => $confidence], JSON_THROW_ON_ERROR),
        );

        return new DiffClassifier($client);
    }

    public function testRecordCheckpoint(): void
    {
        $classifier = $this->createClassifier('factual');

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->expects($this->once())
            ->method('add')
            ->with(
                user_id: 'default',
                session_id: 'test-session',
                task_context: 'Test task',
                ai_proposal: 'AI version',
                human_final: 'Human version',
                diff: 'AI version → Human version',
                tag: 'factual',
                confidence: 'estimated',
            );

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('42');

        $sessionManager = new SessionManager();

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);

        $result = $recorder->recordCheckpoint(
            aiProposal: 'AI version',
            humanFinal: 'Human version',
            taskContext: 'Test task',
            sessionId: 'test-session',
        );

        $this->assertStringContainsString('factual', $result);
        $this->assertStringContainsString('42', $result);
    }

    public function testRecordCheckpointNoDiff(): void
    {
        $classifier = $this->createClassifier('stylistic');

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->expects($this->once())->method('add');

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $sessionManager = new SessionManager();

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);

        $result = $recorder->recordCheckpoint(
            aiProposal: 'Same text',
            humanFinal: 'Same text',
            taskContext: 'No change task',
            sessionId: 'test-session-2',
        );

        $this->assertStringContainsString('stylistic', $result);
    }

    public function testRecordCheckpointUsesActiveSession(): void
    {
        $classifier = $this->createClassifier('strategic');

        $command = $this->createMock(CheckpointCommandInterface::class);

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('5');

        $sessionManager = new SessionManager();
        $sessionManager->startSession('Active task');

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);

        $result = $recorder->recordCheckpoint(
            aiProposal: 'AI',
            humanFinal: 'Human',
            taskContext: 'Task',
        );

        $this->assertStringContainsString('strategic', $result);
        $this->assertStringContainsString('5', $result);
    }

    public function testRecordCheckpointErrorWhenNoSession(): void
    {
        $classifier = $this->createClassifier('factual');
        $command = $this->createMock(CheckpointCommandInterface::class);
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $sessionManager = new SessionManager();

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);

        $result = $recorder->recordCheckpoint(
            aiProposal: 'AI',
            humanFinal: 'Human',
            taskContext: 'Task',
        );

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('No sessionId', $result);
    }

    public function testRecordCheckpointReturnsErrorWhenLastInsertIdReturnsFalse(): void
    {
        $classifier = $this->createClassifier('factual');
        $command = $this->createMock(CheckpointCommandInterface::class);
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn(false);
        $sessionManager = new SessionManager();

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);
        $result = $recorder->recordCheckpoint(
            aiProposal: 'AI',
            humanFinal: 'Human',
            taskContext: 'Task',
            sessionId: 'test-session',
        );

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('database write', $result);
    }

    public function testRecordCheckpointReturnsErrorWhenLastInsertIdReturnsZero(): void
    {
        $classifier = $this->createClassifier('factual');
        $command = $this->createMock(CheckpointCommandInterface::class);
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('0');
        $sessionManager = new SessionManager();

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);
        $result = $recorder->recordCheckpoint(
            aiProposal: 'AI',
            humanFinal: 'Human',
            taskContext: 'Task',
            sessionId: 'test-session',
        );

        $this->assertStringContainsString('Error', $result);
    }

    public function testRecordCheckpointReturnsErrorOnPdoException(): void
    {
        $classifier = $this->createClassifier('factual');
        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->method('add')->willThrowException(new PDOException('UNIQUE constraint failed'));
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $sessionManager = new SessionManager();

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);
        $result = $recorder->recordCheckpoint(
            aiProposal: 'AI',
            humanFinal: 'Human',
            taskContext: 'Task',
            sessionId: 'test-session',
        );

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('UNIQUE constraint failed', $result);
    }

    public function testRecordCheckpointTracksInSession(): void
    {
        $classifier = $this->createClassifier('factual');

        $command = $this->createMock(CheckpointCommandInterface::class);

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('10');

        $sessionManager = new SessionManager();
        $sessionManager->startSession('Track test');

        $recorder = new CheckpointRecorder($classifier, $command, $pdo, $sessionManager);

        $recorder->recordCheckpoint(
            aiProposal: 'AI',
            humanFinal: 'Human',
            taskContext: 'Task',
        );

        $endResult = $sessionManager->endSession();
        $this->assertStringContainsString('1 checkpoints recorded', $endResult);
        $this->assertStringContainsString('1 factual', $endResult);
    }
}
