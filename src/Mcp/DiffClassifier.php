<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class DiffClassifier
{
    private const string MODEL = 'claude-sonnet-4-5-20250929';
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are a diff classifier. Given a diff between an AI proposal and a human's final version, classify the change into exactly one category.

Categories:
- factual: Correction of factual errors (API specs, technical accuracy)
- strategic: Business decisions, policy changes (client requirements, cost decisions)
- stylistic: Formatting, terminology, template conformance

Respond with ONLY valid JSON: {"tag": "factual"|"strategic"|"stylistic", "confidence": "estimated"}
PROMPT;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
    ) {
    }

    /** @return array{tag: string, confidence: string} */
    public function classify(string $diff): array
    {
        $body = json_encode([
            'model' => self::MODEL,
            'max_tokens' => 100,
            'messages' => [
                ['role' => 'user', 'content' => "Classify this diff:\n\n{$diff}"],
            ],
            'system' => self::SYSTEM_PROMPT,
        ], JSON_THROW_ON_ERROR);

        $request = new Request('POST', self::API_URL, [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ], $body);

        $response = $this->httpClient->send($request);
        $responseBody = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $text = $responseBody['content'][0]['text'] ?? '';
        $classification = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

        return [
            'tag' => $classification['tag'] ?? 'stylistic',
            'confidence' => $classification['confidence'] ?? 'estimated',
        ];
    }
}
