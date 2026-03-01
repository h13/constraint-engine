<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
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
        $mock = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $classifier = new DiffClassifier($client, '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY is not configured');
        $classifier->classify('some diff');
    }

    public function testClassifyHttpError(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $classifier = new DiffClassifier($client, 'test-key');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API request failed');
        $classifier->classify('some diff');
    }

    public function testClassifyMalformedResponse(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"content": []}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $classifier = new DiffClassifier($client, 'test-key');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected Anthropic API response structure');
        $classifier->classify('some diff');
    }

    public function testClassifyInvalidTagFallsBackToStylistic(): void
    {
        $responseBody = json_encode([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'tag' => 'unknown_tag',
                        'confidence' => 'estimated',
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $classifier = new DiffClassifier($client, 'test-key');

        $result = $classifier->classify('some diff');
        $this->assertSame('stylistic', $result['tag']);
    }

    private function createClassifier(string $tag, string $confidence): DiffClassifier
    {
        $responseBody = json_encode([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'tag' => $tag,
                        'confidence' => $confidence,
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new DiffClassifier($client, 'test-api-key');
    }
}
