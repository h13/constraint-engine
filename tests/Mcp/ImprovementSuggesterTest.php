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
            $this->fail('Schema file not found: var/sql/sqlite/create_checkpoint.sql');
        }

        $pdo->exec($sql);
    }

    private function createSuggester(string $aiResponse): ImprovementSuggester
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn($aiResponse);

        return new ImprovementSuggester($this->query, $client);
    }

    public function testSuggestImprovementsNoData(): void
    {
        $suggester = $this->createSuggester('');
        $result = $suggester->suggestImprovements('Textフィールドを使用', 'SF項目設計');

        $this->assertStringContainsString('No past modification data', $result);
    }

    public function testSuggestImprovementsApiError(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'error-test',
            'taskContext' => 'SF項目設計',
            'aiProposal' => 'Text',
            'humanFinal' => 'LTA',
            'diff' => 'Text→LTA',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willThrowException(new RuntimeException('API connection failed'));

        $suggester = new ImprovementSuggester($this->query, $client);
        $result = $suggester->suggestImprovements('Textフィールドを使用', 'SF項目設計');

        $this->assertStringContainsString('Error:', $result);
        $this->assertStringContainsString('API connection failed', $result);
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
