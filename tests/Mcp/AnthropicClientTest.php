<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class AnthropicClientTest extends TestCase
{
    public function testCompleteThrowsOnEmptyApiKey(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $client = new AnthropicClient($http, '', 'claude-sonnet-4-5-20250929');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY is not configured');
        $client->complete('system', 'hello', 100);
    }

    public function testCompleteThrowsOnEmptyModel(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $client = new AnthropicClient($http, 'sk-test', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_MODEL is not configured');
        $client->complete('system', 'hello', 100);
    }

    public function testCompleteReturnsText(): void
    {
        $body = json_encode([
            'content' => [['text' => 'AI response']],
        ], JSON_THROW_ON_ERROR);

        $http = $this->createMock(ClientInterface::class);
        $http->method('send')->willReturn(new Response(200, [], $body));

        $client = new AnthropicClient($http, 'sk-test', 'claude-sonnet-4-5-20250929');
        $result = $client->complete('system prompt', 'user message', 100);

        $this->assertSame('AI response', $result);
    }

    public function testCompleteSendsCorrectHeaders(): void
    {
        $body = json_encode([
            'content' => [['text' => 'ok']],
        ], JSON_THROW_ON_ERROR);

        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (Request $request): bool {
                return $request->getHeaderLine('x-api-key') === 'sk-test-key'
                    && $request->getHeaderLine('anthropic-version') === '2023-06-01'
                    && $request->getHeaderLine('Content-Type') === 'application/json';
            }))
            ->willReturn(new Response(200, [], $body));

        $client = new AnthropicClient($http, 'sk-test-key', 'claude-sonnet-4-5-20250929');
        $client->complete('sys', 'usr', 100);
    }

    public function testCompleteSendsCorrectBody(): void
    {
        $body = json_encode([
            'content' => [['text' => 'ok']],
        ], JSON_THROW_ON_ERROR);

        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (Request $request): bool {
                $decoded = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

                return $decoded['model'] === 'test-model'
                    && $decoded['max_tokens'] === 200
                    && $decoded['system'] === 'system prompt'
                    && $decoded['messages'][0]['role'] === 'user'
                    && $decoded['messages'][0]['content'] === 'user message';
            }))
            ->willReturn(new Response(200, [], $body));

        $client = new AnthropicClient($http, 'sk-key', 'test-model');
        $client->complete('system prompt', 'user message', 200);
    }

    public function testCompleteThrowsOnGuzzleException(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->method('send')->willThrowException(
            new RequestException('Connection failed', new Request('POST', 'https://api.anthropic.com')),
        );

        $client = new AnthropicClient($http, 'sk-test', 'model');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API request failed');
        $client->complete('sys', 'usr', 100);
    }

    public function testCompleteThrowsOnNon200Status(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->method('send')->willReturn(new Response(429, [], '{"error": "rate limited"}'));

        $client = new AnthropicClient($http, 'sk-test', 'model');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API error: HTTP 429 — {"error": "rate limited"}');
        $client->complete('sys', 'usr', 100);
    }

    public function testCompleteThrowsOnMalformedResponse(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->method('send')->willReturn(new Response(200, [], '{"content": []}'));

        $client = new AnthropicClient($http, 'sk-test', 'model');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected Anthropic API response structure');
        $client->complete('sys', 'usr', 100);
    }

    public function testCompleteThrowsOnInvalidJson(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->method('send')->willReturn(new Response(200, [], 'not json'));

        $client = new AnthropicClient($http, 'sk-test', 'model');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API returned invalid JSON');
        $client->complete('sys', 'usr', 100);
    }
}
