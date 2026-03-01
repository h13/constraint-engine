<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use PHPUnit\Framework\TestCase;

class CheckpointRecorderTest extends TestCase
{
    public function testRecordCheckpoint(): void
    {
        $classifier = $this->createMock(DiffClassifier::class);
        $classifier->method('classify')
            ->willReturn(['tag' => 'factual', 'confidence' => 'estimated']);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->expects($this->once())
            ->method('add')
            ->with(
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

        $recorder = new CheckpointRecorder($classifier, $command, $pdo);

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
        $classifier = $this->createMock(DiffClassifier::class);
        $classifier->method('classify')
            ->with('(no changes)')
            ->willReturn(['tag' => 'stylistic', 'confidence' => 'estimated']);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->expects($this->once())->method('add');

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $recorder = new CheckpointRecorder($classifier, $command, $pdo);

        $result = $recorder->recordCheckpoint(
            aiProposal: 'Same text',
            humanFinal: 'Same text',
            taskContext: 'No change task',
            sessionId: 'test-session-2',
        );

        $this->assertStringContainsString('stylistic', $result);
    }
}
