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

class ImprovementSuggesterTest extends TestCase
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

    private function createSuggester(string $aiResponse): ImprovementSuggester
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

        return new ImprovementSuggester($this->query, $client, 'test-api-key', 'test-model');
    }

    public function testSuggestImprovementsNoData(): void
    {
        $suggester = $this->createSuggester('');
        $result = $suggester->suggestImprovements('Textフィールドを使用', 'SF項目設計');

        $this->assertStringContainsString('No past modification data', $result);
    }

    public function testSuggestImprovementsWithData(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'suggest-test',
            'taskContext' => 'SF項目設計',
            'aiProposal' => 'Text',
            'humanFinal' => 'LongTextArea',
            'diff' => 'Text→LTA',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $suggester = $this->createSuggester('1. SF項目設計では過去にTextをLongTextAreaに変更した履歴があります。LongTextAreaの使用を推奨します。');
        $result = $suggester->suggestImprovements('Textフィールドを使用', 'SF項目設計');

        $this->assertStringContainsString('LongTextArea', $result);
    }
}
