<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use function in_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class DiffClassifier
{
    private const int MAX_TOKENS = 100;
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are a diff classifier. Given a diff between an AI proposal and a human's final version, classify the change into exactly one category.

Categories:
- factual: Correction of factual errors (API specs, technical accuracy)
- strategic: Business decisions, policy changes (client requirements, cost decisions)
- stylistic: Formatting, terminology, template conformance

Respond with ONLY valid JSON: {"tag": "factual"|"strategic"|"stylistic", "confidence": "estimated"}
PROMPT;

    public function __construct(
        private readonly AnthropicClientInterface $client,
    ) {
    }

    /** @return array{tag: string, confidence: string} */
    public function classify(string $diff): array
    {
        $text = $this->client->complete(
            self::SYSTEM_PROMPT,
            "Classify this diff:\n\n{$diff}",
            self::MAX_TOKENS,
        );

        $classification = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        $tag = $classification['tag'] ?? null;
        if (! in_array($tag, ['factual', 'strategic', 'stylistic'], true)) {
            $tag = 'stylistic';
        }

        return [
            'tag' => $tag,
            'confidence' => $classification['confidence'] ?? 'estimated',
        ];
    }
}
