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

final class DescriptionParser
{
    private const string MODEL = 'claude-sonnet-4-5-20250929';
    private const string API_URL = 'https://api.anthropic.com/v1/messages';
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are a structured data extractor for an AI-human collaboration tracking system.

Given a free-text description of how a human modified an AI proposal, extract the following fields:
- aiProposal: What the AI originally suggested (brief)
- humanFinal: What the human changed it to (brief)
- taskContext: The domain/task being worked on (brief)
- tag: Classification of the change — one of "factual", "strategic", "stylistic"
  - factual: Correction of factual errors (API specs, technical accuracy)
  - strategic: Business decisions, policy changes (client requirements, cost decisions)
  - stylistic: Formatting, terminology, template conformance
- confidence: Always "estimated"

Respond with ONLY valid JSON: {"aiProposal": "...", "humanFinal": "...", "taskContext": "...", "tag": "factual"|"strategic"|"stylistic", "confidence": "estimated"}
PROMPT;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey,
    ) {
    }

    /** @return array{aiProposal: string, humanFinal: string, taskContext: string, tag: string, confidence: string} */
    public function parse(string $description): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured. Set the environment variable before using parsing features.');
        }

        $body = json_encode([
            'model' => self::MODEL,
            'max_tokens' => 300,
            'messages' => [
                ['role' => 'user', 'content' => "Extract structured data from this description:\n\n{$description}"],
            ],
            'system' => self::SYSTEM_PROMPT,
        ], JSON_THROW_ON_ERROR);

        $request = new Request('POST', self::API_URL, [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ], $body);

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Anthropic API request failed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Anthropic API error: HTTP ' . $response->getStatusCode());
        }

        $responseBody = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (! isset($responseBody['content'][0]['text'])) {
            throw new RuntimeException('Unexpected Anthropic API response structure');
        }

        $text = $responseBody['content'][0]['text'];
        $parsed = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

        return [
            'aiProposal' => $parsed['aiProposal'] ?? '',
            'humanFinal' => $parsed['humanFinal'] ?? '',
            'taskContext' => $parsed['taskContext'] ?? '',
            'tag' => $parsed['tag'] ?? 'stylistic',
            'confidence' => 'estimated',
        ];
    }
}
