<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Module;

use ConstraintEngine\App\Mcp\DiffClassifier;
use ConstraintEngine\App\Mcp\SessionManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class McpModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(ClientInterface::class)->to(Client::class)->in(Scope::SINGLETON);
        $this->bind(DiffClassifier::class)->toConstructor(
            DiffClassifier::class,
            ['apiKey' => 'anthropic_api_key'],
        );
        $this->bind()->annotatedWith('anthropic_api_key')->toInstance(
            $_ENV['ANTHROPIC_API_KEY'] ?? '',
        );
        $this->bind(SessionManager::class)->in(Scope::SINGLETON);
    }
}
