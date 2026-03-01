<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Module;

use ConstraintEngine\App\Mcp\DescriptionParser;
use ConstraintEngine\App\Mcp\DiffClassifier;
use ConstraintEngine\App\Mcp\ImprovementSuggester;
use ConstraintEngine\App\Mcp\InsightGenerator;
use ConstraintEngine\App\Mcp\SessionAnalyzer;
use ConstraintEngine\App\Mcp\SessionManager;
use ConstraintEngine\App\Mcp\TemplateSuggester;
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
        $this->bind(DescriptionParser::class)->toConstructor(
            DescriptionParser::class,
            ['apiKey' => 'anthropic_api_key'],
        );
        $this->bind(SessionAnalyzer::class)->toConstructor(
            SessionAnalyzer::class,
            ['apiKey' => 'anthropic_api_key'],
        );
        $this->bind(ImprovementSuggester::class)->toConstructor(
            ImprovementSuggester::class,
            ['apiKey' => 'anthropic_api_key'],
        );
        $this->bind(InsightGenerator::class)->toConstructor(
            InsightGenerator::class,
            ['apiKey' => 'anthropic_api_key'],
        );
        $this->bind(TemplateSuggester::class)->toConstructor(
            TemplateSuggester::class,
            ['apiKey' => 'anthropic_api_key'],
        );
        $this->bind(SessionManager::class)->in(Scope::SINGLETON);
    }
}
