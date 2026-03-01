<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class DiffClassifierTest extends TestCase
{
    public function testClassifyFactual(): void
    {
        $classifier = $this->createClassifier('factual', 'estimated');

        $result = $classifier->classify('Text → LongTextArea');

        $this->assertSame('factual', $result['tag']);
        $this->assertSame('estimated', $result['confidence']);
    }

    public function testClassifyStrategic(): void
    {
        $classifier = $this->createClassifier('strategic', 'estimated');

        $result = $classifier->classify('Standard plan → Enterprise plan');

        $this->assertSame('strategic', $result['tag']);
    }

    public function testClassifyStylistic(): void
    {
        $classifier = $this->createClassifier('stylistic', 'estimated');

        $result = $classifier->classify('user_name → userName');

        $this->assertSame('stylistic', $result['tag']);
    }

    public function testClassifyMissingApiKey(): void
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')
            ->willThrowException(new RuntimeException('ANTHROPIC_API_KEY is not configured'));

        $classifier = new DiffClassifier($client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY is not configured');
        $classifier->classify('some diff');
    }

    public function testClassifyHttpError(): void
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')
            ->willThrowException(new RuntimeException('Anthropic API request failed'));

        $classifier = new DiffClassifier($client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API request failed');
        $classifier->classify('some diff');
    }

    public function testClassifyMalformedResponseFallsBackToStylistic(): void
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn('not json');

        $classifier = new DiffClassifier($client);
        $result = $classifier->classify('some diff');

        $this->assertSame('stylistic', $result['tag']);
        $this->assertSame('estimated', $result['confidence']);
    }

    public function testClassifyInvalidConfidenceFallsBackToEstimated(): void
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn(
            json_encode(['tag' => 'factual', 'confidence' => 'hallucinated_value'], JSON_THROW_ON_ERROR),
        );

        $classifier = new DiffClassifier($client);
        $result = $classifier->classify('some diff');
        $this->assertSame('factual', $result['tag']);
        $this->assertSame('estimated', $result['confidence']);
    }

    public function testClassifyStatedConfidenceIsAllowed(): void
    {
        $classifier = $this->createClassifier('factual', 'stated');

        $result = $classifier->classify('Text → LongTextArea');

        $this->assertSame('stated', $result['confidence']);
    }

    public function testClassifyInvalidTagFallsBackToStylistic(): void
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn(
            json_encode(['tag' => 'unknown_tag', 'confidence' => 'estimated'], JSON_THROW_ON_ERROR),
        );

        $classifier = new DiffClassifier($client);
        $result = $classifier->classify('some diff');
        $this->assertSame('stylistic', $result['tag']);
    }

    private function createClassifier(string $tag, string $confidence): DiffClassifier
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn(
            json_encode(['tag' => $tag, 'confidence' => $confidence], JSON_THROW_ON_ERROR),
        );

        return new DiffClassifier($client);
    }
}
