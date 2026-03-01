<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use RuntimeException;

interface AnthropicClientInterface
{
    /**
     * Send a completion request to the Anthropic API.
     *
     * @throws RuntimeException If API key is missing, request fails, or response is malformed.
     */
    public function complete(string $systemPrompt, string $userMessage, int $maxTokens): string;
}
