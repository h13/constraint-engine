<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use ConstraintEngine\App\Injector;
use ConstraintEngine\App\Query\CheckpointQueryInterface;
use ConstraintEngine\App\TestModule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_get_contents;

class SessionAnalyzerTest extends TestCase
{
    private CheckpointQueryInterface $query;
    private ResourceInterface $resource;
    private ExtendedPdoInterface $pdo;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('app', new TestModule());
        $this->query = $injector->getInstance(CheckpointQueryInterface::class);
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $sql = file_get_contents(__DIR__ . '/../../var/sql/sqlite/create_checkpoint.sql');
        if ($sql === false) {
            $this->fail('Schema file not found: var/sql/sqlite/create_checkpoint.sql');
        }

        $this->pdo->exec($sql);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM checkpoint_recall');
        $this->pdo->exec('DELETE FROM checkpoint');
    }

    private function createAnalyzer(string $aiResponse): SessionAnalyzer
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn($aiResponse);

        return new SessionAnalyzer($this->query, $client);
    }

    public function testAnalyzeSessionNotFound(): void
    {
        $analyzer = $this->createAnalyzer('');
        $result = $analyzer->analyzeSession('nonexistent');

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('No checkpoints found', $result);
    }

    public function testAnalyzeSessionApiError(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'error-test',
            'taskContext' => 'テスト',
            'aiProposal' => 'A',
            'humanFinal' => 'B',
            'diff' => 'A→B',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willThrowException(new RuntimeException('API connection failed'));

        $analyzer = new SessionAnalyzer($this->query, $client);
        $result = $analyzer->analyzeSession('error-test');

        $this->assertStringContainsString('Error:', $result);
        $this->assertStringContainsString('Failed to analyze session', $result);
        $this->assertStringNotContainsString('API connection failed', $result);
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
