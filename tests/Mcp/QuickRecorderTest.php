<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use ConstraintEngine\App\Query\CheckpointCommandInterface;
use PDOException;
use PHPUnit\Framework\TestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class QuickRecorderTest extends TestCase
{
    /** @param array<string, string> $responseData */
    private function createParser(array $responseData): DescriptionParser
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn(
            json_encode($responseData, JSON_THROW_ON_ERROR),
        );

        return new DescriptionParser($client);
    }

    public function testQuickRecord(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'Textフィールド',
            'humanFinal' => 'LongTextArea',
            'taskContext' => 'SF項目設計',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->expects($this->once())
            ->method('add')
            ->with(
                user_id: 'default',
                session_id: $this->isType('string'),
                task_context: 'SF項目設計',
                ai_proposal: 'Textフィールド',
                human_final: 'LongTextArea',
                diff: 'Textフィールド → LongTextArea',
                tag: 'factual',
                confidence: 'estimated',
            );

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('7');

        $sessionManager = new SessionManager();
        $sessionManager->startSession('SF設計');

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);

        $result = $recorder->quickRecord('SF項目設計でTextをLongTextAreaに変更');

        $this->assertStringContainsString('factual', $result);
        $this->assertStringContainsString('7', $result);
    }

    public function testQuickRecordErrorWhenNoSession(): void
    {
        $parser = $this->createParser(['tag' => 'factual']);
        $command = $this->createMock(CheckpointCommandInterface::class);
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $sessionManager = new SessionManager();

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);

        $result = $recorder->quickRecord('何かの変更');

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('No active session', $result);
    }

    public function testQuickRecordFallsBackToSessionContext(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'A',
            'humanFinal' => 'B',
            'taskContext' => '',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->expects($this->once())
            ->method('add')
            ->with(
                user_id: 'default',
                session_id: $this->isType('string'),
                task_context: 'セッションコンテキスト',
                ai_proposal: 'A',
                human_final: 'B',
                diff: 'A → B',
                tag: 'strategic',
                confidence: 'estimated',
            );

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('3');

        $sessionManager = new SessionManager();
        $sessionManager->startSession('セッションコンテキスト');

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);

        $result = $recorder->quickRecord('AをBに変更');

        $this->assertStringContainsString('strategic', $result);
    }

    public function testQuickRecordReturnsErrorWhenTaskContextIsWhitespace(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'A',
            'humanFinal' => 'B',
            'taskContext' => '   ',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $pdo = $this->createMock(ExtendedPdoInterface::class);

        $sessionManager = new SessionManager();
        $sessionManager->startSession('Valid context');

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);
        $result = $recorder->quickRecord('AをBに変更');

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Could not determine taskContext', $result);
    }

    public function testQuickRecordReturnsErrorWhenLastInsertIdReturnsFalse(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'A',
            'humanFinal' => 'B',
            'taskContext' => 'Test',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn(false);

        $sessionManager = new SessionManager();
        $sessionManager->startSession('Test');

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);
        $result = $recorder->quickRecord('AをBに変更');

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('database write', $result);
    }

    public function testQuickRecordReturnsErrorOnPdoException(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'A',
            'humanFinal' => 'B',
            'taskContext' => 'Test',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $command = $this->createMock(CheckpointCommandInterface::class);
        $command->method('add')->willThrowException(new PDOException('disk I/O error'));
        $pdo = $this->createMock(ExtendedPdoInterface::class);

        $sessionManager = new SessionManager();
        $sessionManager->startSession('Test');

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);
        $result = $recorder->quickRecord('AをBに変更');

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Failed to write checkpoint', $result);
        $this->assertStringNotContainsString('disk I/O error', $result);
    }

    public function testQuickRecordTracksInSession(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'X',
            'humanFinal' => 'Y',
            'taskContext' => 'Test',
            'tag' => 'stylistic',
            'confidence' => 'estimated',
        ]);

        $command = $this->createMock(CheckpointCommandInterface::class);

        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $sessionManager = new SessionManager();
        $sessionManager->startSession('Test');

        $recorder = new QuickRecorder($parser, $command, $pdo, $sessionManager);
        $recorder->quickRecord('XをYに変更');

        $endResult = $sessionManager->endSession();
        $this->assertStringContainsString('1 checkpoints recorded', $endResult);
        $this->assertStringContainsString('1 stylistic', $endResult);
    }
}
