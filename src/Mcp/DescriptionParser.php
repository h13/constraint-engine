<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Semantic\Tag;
use JsonException;
use RuntimeException;

use function in_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class DescriptionParser
{
    private const int MAX_TOKENS = 300;
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
        private readonly AnthropicClientInterface $client,
    ) {
    }

    /** @return array{aiProposal: string, humanFinal: string, taskContext: string, tag: string, confidence: string} */
    public function parse(string $description): array
    {
        try {
            $text = $this->client->complete(
                self::SYSTEM_PROMPT,
                "Extract structured data from this description:\n\n{$description}",
                self::MAX_TOKENS,
            );
        } catch (RuntimeException) {
            return [
                'aiProposal' => '',
                'humanFinal' => '',
                'taskContext' => '',
                'tag' => 'stylistic',
                'confidence' => 'estimated',
            ];
        }

        try {
            $parsed = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [
                'aiProposal' => '',
                'humanFinal' => '',
                'taskContext' => '',
                'tag' => 'stylistic',
                'confidence' => 'estimated',
            ];
        }

        $tag = $parsed['tag'] ?? 'stylistic';
        if (! in_array($tag, Tag::VALID, true)) {
            $tag = 'stylistic';
        }

        return [
            'aiProposal' => $parsed['aiProposal'] ?? '',
            'humanFinal' => $parsed['humanFinal'] ?? '',
            'taskContext' => $parsed['taskContext'] ?? '',
            'tag' => $tag,
            'confidence' => 'estimated',
        ];
    }
}
