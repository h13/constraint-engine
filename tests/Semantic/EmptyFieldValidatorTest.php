<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\SemanticVariable\SemanticValidator;
use ConstraintEngine\App\Exception\EmptyFieldException;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;

class EmptyFieldValidatorTest extends TestCase
{
    private SemanticValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SemanticValidator('ConstraintEngine\App\Semantic');
    }

    public function testValidSessionId(): void
    {
        $this->assertValid($this->paramFor('sessionId'), 'sess-001');
    }

    public function testEmptySessionId(): void
    {
        $this->assertInvalid($this->paramFor('sessionId'), '');
    }

    public function testWhitespaceSessionId(): void
    {
        $this->assertInvalid($this->paramFor('sessionId'), '   ');
    }

    public function testValidTaskContext(): void
    {
        $this->assertValid($this->paramFor('taskContext'), 'Salesforce設計');
    }

    public function testEmptyTaskContext(): void
    {
        $this->assertInvalid($this->paramFor('taskContext'), '');
    }

    public function testValidAiProposal(): void
    {
        $this->assertValid($this->paramFor('aiProposal'), 'Textフィールドを使用');
    }

    public function testEmptyAiProposal(): void
    {
        $this->assertInvalid($this->paramFor('aiProposal'), '');
    }

    public function testValidHumanFinal(): void
    {
        $this->assertValid($this->paramFor('humanFinal'), 'LongTextAreaに変更');
    }

    public function testEmptyHumanFinal(): void
    {
        $this->assertInvalid($this->paramFor('humanFinal'), '');
    }

    public function testValidDiff(): void
    {
        $this->assertValid($this->paramFor('diff'), 'Text→LongTextArea');
    }

    public function testEmptyDiff(): void
    {
        $this->assertInvalid($this->paramFor('diff'), '');
    }

    public function testValidUserId(): void
    {
        $this->assertValid($this->paramFor('userId'), 'default');
    }

    public function testEmptyUserId(): void
    {
        $this->assertInvalid($this->paramFor('userId'), '');
    }

    private function paramFor(string $name): ReflectionParameter
    {
        $closures = [
            'sessionId' => static function (string $sessionId): void {
            },
            'taskContext' => static function (string $taskContext): void {
            },
            'aiProposal' => static function (string $aiProposal): void {
            },
            'humanFinal' => static function (string $humanFinal): void {
            },
            'diff' => static function (string $diff): void {
            },
            'userId' => static function (string $userId): void {
            },
        ];

        return (new ReflectionFunction($closures[$name]))->getParameters()[0];
    }

    private function assertValid(ReflectionParameter $param, string $value): void
    {
        $errors = $this->validator->validateArg($param, $value);
        $this->assertFalse($errors->hasErrors());
    }

    private function assertInvalid(ReflectionParameter $param, string $value): void
    {
        $errors = $this->validator->validateArg($param, $value);
        $this->assertTrue($errors->hasErrors());
        $this->assertInstanceOf(EmptyFieldException::class, $errors->exceptions[0]);
    }
}
