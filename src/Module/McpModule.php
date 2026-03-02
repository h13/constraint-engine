<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Module;

use ConstraintEngine\App\Mcp\AnthropicClient;
use ConstraintEngine\App\Mcp\AnthropicClientInterface;
use ConstraintEngine\App\Mcp\SessionManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class McpModule extends AbstractModule
{
    private const string DEFAULT_MODEL = 'claude-sonnet-4-5-20250929';
    private const string DEFAULT_API_BASE_URL = 'http://localhost:8080';

    protected function configure(): void
    {
        $this->bind(ClientInterface::class)->to(Client::class)->in(Scope::SINGLETON);
        $this->bind(AnthropicClientInterface::class)->toConstructor(
            AnthropicClient::class,
            [
                'apiKey' => 'anthropic_api_key',
                'model' => 'anthropic_model',
            ],
        )->in(Scope::SINGLETON);
        $this->bind()->annotatedWith('anthropic_api_key')->toInstance(
            $_ENV['ANTHROPIC_API_KEY'] ?? '',
        );
        $this->bind()->annotatedWith('anthropic_model')->toInstance(
            $_ENV['ANTHROPIC_MODEL'] ?? self::DEFAULT_MODEL,
        );
        $this->bind()->annotatedWith('api_base_url')->toInstance(
            $_ENV['API_BASE_URL'] ?? self::DEFAULT_API_BASE_URL,
        );
        $this->bind(SessionManager::class)->in(Scope::SINGLETON);
    }
}
