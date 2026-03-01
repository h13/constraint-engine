<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use ConstraintEngine\App\Injector;
use ConstraintEngine\App\Query\CheckpointQueryInterface;
use ConstraintEngine\App\TestModule;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class InsightGeneratorTest extends TestCase
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

    private function createGenerator(string $aiResponse): InsightGenerator
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn($aiResponse);

        return new InsightGenerator($this->query, $client);
    }

    public function testGenerateInsightsInsufficientData(): void
    {
        $generator = $this->createGenerator('');
        $result = $generator->generateInsights();

        $this->assertStringContainsString('Insufficient data', $result);
        $this->assertStringContainsString('0 checkpoints', $result);
    }

    public function testGenerateInsightsWithEnoughData(): void
    {
        $tags = ['factual', 'strategic', 'stylistic'];
        for ($i = 0; $i < 12; $i++) {
            $tag = $tags[$i % 3];
            $this->resource->post('page://self/checkpoints', [
                'sessionId' => 'insight-test',
                'taskContext' => "タスク{$i}",
                'aiProposal' => "提案{$i}",
                'humanFinal' => "最終{$i}",
                'diff' => "提案{$i}→最終{$i}",
                'tag' => $tag,
                'confidence' => 'estimated',
            ]);
        }

        $generator = $this->createGenerator('Stylisticの修正が33%を占めています。社内テンプレートの整備を推奨します。');
        $result = $generator->generateInsights();

        $this->assertStringContainsString('Stylistic', $result);
    }
}
