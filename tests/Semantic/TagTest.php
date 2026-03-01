<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Semantic;

use Be\Framework\SemanticVariable\SemanticValidator;
use ConstraintEngine\App\Exception\InvalidTagException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

class TagTest extends TestCase
{
    private SemanticValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SemanticValidator('ConstraintEngine\App\Semantic');
    }

    /** @return array<string, array{string}> */
    public static function validTagProvider(): array
    {
        return [
            'factual' => ['factual'],
            'strategic' => ['strategic'],
            'stylistic' => ['stylistic'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function invalidTagProvider(): array
    {
        return [
            'empty string' => [''],
            'uppercase' => ['FACTUAL'],
            'typo' => ['facutal'],
            'unknown value' => ['critical'],
        ];
    }

    #[DataProvider('validTagProvider')]
    public function testValidTag(string $value): void
    {
        $fn = static function (string $tag): void {
        };
        $param = (new ReflectionFunction($fn))->getParameters()[0];
        $errors = $this->validator->validateArg($param, $value);
        $this->assertFalse($errors->hasErrors());
    }

    #[DataProvider('invalidTagProvider')]
    public function testInvalidTag(string $value): void
    {
        $fn = static function (string $tag): void {
        };
        $param = (new ReflectionFunction($fn))->getParameters()[0];
        $errors = $this->validator->validateArg($param, $value);
        $this->assertTrue($errors->hasErrors());
        $this->assertInstanceOf(InvalidTagException::class, $errors->exceptions[0]);
    }
}
