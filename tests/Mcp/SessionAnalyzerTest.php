<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use ConstraintEngine\App\Injector;
use ConstraintEngine\App\Query\CheckpointQueryInterface;
use ConstraintEngine\App\TestModule;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class SessionAnalyzerTest extends TestCase
{
    private CheckpointQueryInterface $query;
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('app', new TestModule());
        $this->query = $injector->getInstance(CheckpointQueryInterface::class);
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $sql = file_get_contents(__DIR__ . '/../../var/sql/sqlite/create_checkpoint.sql');
        if ($sql === false) {
            return;
        }

        $pdo->exec($sql);
    }

    private function createAnalyzer(string $aiResponse): SessionAnalyzer
    {
        $responseBody = json_encode([
            'content' => [
                ['type' => 'text', 'text' => $aiResponse],
            ],
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new SessionAnalyzer($this->query, $client, 'test-api-key', 'test-model');
    }

    public function testAnalyzeSessionNotFound(): void
    {
        $analyzer = $this->createAnalyzer('');
        $result = $analyzer->analyzeSession('nonexistent');

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('No checkpoints found', $result);
    }

    public function testAnalyzeSession(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'analyze-test',
            'taskContext' => 'SF設計',
            'aiProposal' => 'Text',
            'humanFinal' => 'LongTextArea',
            'diff' => 'Text→LTA',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'analyze-test',
            'taskContext' => 'SF設計',
            'aiProposal' => 'Standard',
            'humanFinal' => 'Enterprise',
            'diff' => 'Std→Ent',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);

        $analyzer = $this->createAnalyzer('Factualの修正が多い傾向です。API仕様の確認を強化しましょう。');
        $result = $analyzer->analyzeSession('analyze-test');

        $this->assertStringContainsString('Factual', $result);
    }
}
