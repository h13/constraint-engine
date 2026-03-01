<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

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
