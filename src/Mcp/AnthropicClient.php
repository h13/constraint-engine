<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class AnthropicClient implements AnthropicClientInterface
{
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string API_VERSION = '2023-06-01';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    /**
     * Send a completion request to the Anthropic API.
     *
     * @throws RuntimeException If API key is missing, request fails, or response is malformed.
     */
    public function complete(string $systemPrompt, string $userMessage, int $maxTokens): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured. Set the environment variable before using AI features.');
        }

        if ($this->model === '') {
            throw new RuntimeException('ANTHROPIC_MODEL is not configured. Set the environment variable or provide a default model.');
        }

        $body = json_encode([
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'system' => $systemPrompt,
        ], JSON_THROW_ON_ERROR);

        $request = new Request('POST', self::API_URL, [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
        ], $body);

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Anthropic API request failed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            $errorBody = (string) $response->getBody();

            throw new RuntimeException('Anthropic API error: HTTP ' . $response->getStatusCode() . ' — ' . $errorBody);
        }

        $responseBody = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (! isset($responseBody['content'][0]['text'])) {
            throw new RuntimeException('Unexpected Anthropic API response structure');
        }

        return $responseBody['content'][0]['text'];
    }
}
