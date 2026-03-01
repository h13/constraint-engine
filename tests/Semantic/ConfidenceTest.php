<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\SemanticVariable\SemanticValidator;
use ConstraintEngine\App\Exception\InvalidConfidenceException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

class ConfidenceTest extends TestCase
{
    private SemanticValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SemanticValidator('ConstraintEngine\App\Semantic');
    }

    /** @return array<string, array{string}> */
    public static function validConfidenceProvider(): array
    {
        return [
            'estimated' => ['estimated'],
            'stated' => ['stated'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function invalidConfidenceProvider(): array
    {
        return [
            'empty string' => [''],
            'uppercase' => ['ESTIMATED'],
            'unknown value' => ['guessed'],
        ];
    }

    #[DataProvider('validConfidenceProvider')]
    public function testValidConfidence(string $value): void
    {
        $fn = static function (string $confidence): void {
        };
        $param = (new ReflectionFunction($fn))->getParameters()[0];
        $errors = $this->validator->validateArg($param, $value);
        $this->assertFalse($errors->hasErrors());
    }

    #[DataProvider('invalidConfidenceProvider')]
    public function testInvalidConfidence(string $value): void
    {
        $fn = static function (string $confidence): void {
        };
        $param = (new ReflectionFunction($fn))->getParameters()[0];
        $errors = $this->validator->validateArg($param, $value);
        $this->assertTrue($errors->hasErrors());
        $this->assertInstanceOf(InvalidConfidenceException::class, $errors->exceptions[0]);
    }
}
