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

class TemplateSuggesterTest extends TestCase
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

    private function createSuggester(string $aiResponse): TemplateSuggester
    {
        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willReturn($aiResponse);

        return new TemplateSuggester($this->query, $client);
    }

    public function testSuggestTemplateInsufficientData(): void
    {
        $suggester = $this->createSuggester('');
        $result = $suggester->suggestTemplate();

        $this->assertStringContainsString('Insufficient stylistic data', $result);
        $this->assertStringContainsString('0 stylistic checkpoints', $result);
    }

    public function testSuggestTemplateWithPartialData(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'tmpl-001',
            'taskContext' => 'document',
            'aiProposal' => 'formal',
            'humanFinal' => 'casual',
            'diff' => 'formal to casual',
            'tag' => 'stylistic',
            'confidence' => 'estimated',
        ]);

        $suggester = $this->createSuggester('');
        $result = $suggester->suggestTemplate();

        $this->assertStringContainsString('Insufficient stylistic data', $result);
        $this->assertStringContainsString('1 stylistic checkpoints', $result);
    }

    public function testSuggestTemplateApiError(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->resource->post('page://self/checkpoints', [
                'sessionId' => 'error-test',
                'taskContext' => 'document',
                'aiProposal' => "proposal{$i}",
                'humanFinal' => "final{$i}",
                'diff' => "proposal{$i} to final{$i}",
                'tag' => 'stylistic',
                'confidence' => 'estimated',
            ]);
        }

        $client = $this->createMock(AnthropicClientInterface::class);
        $client->method('complete')->willThrowException(new RuntimeException('API connection failed'));

        $suggester = new TemplateSuggester($this->query, $client);
        $result = $suggester->suggestTemplate();

        $this->assertStringContainsString('Error:', $result);
        $this->assertStringContainsString('Failed to generate templates', $result);
        $this->assertStringNotContainsString('API connection failed', $result);
    }

    public function testSuggestTemplateWithEnoughData(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->resource->post('page://self/checkpoints', [
                'sessionId' => 'tmpl-001',
                'taskContext' => 'document',
                'aiProposal' => "proposal{$i}",
                'humanFinal' => "final{$i}",
                'diff' => "proposal{$i} to final{$i}",
                'tag' => 'stylistic',
                'confidence' => 'estimated',
            ]);
        }

        $suggester = $this->createSuggester("Template 1: keigo\nRule: use casual\nExample: formal to casual");
        $result = $suggester->suggestTemplate();

        $this->assertStringContainsString('Shared Style Templates', $result);
        $this->assertStringContainsString('4 stylistic corrections', $result);
        $this->assertStringContainsString('Template', $result);
    }

    public function testSuggestTemplateIgnoresNonStylistic(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'tmpl-002',
            'taskContext' => 'SF',
            'aiProposal' => 'Text',
            'humanFinal' => 'LongTextArea',
            'diff' => 'Text to LTA',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'tmpl-002',
            'taskContext' => 'SF',
            'aiProposal' => 'Standard',
            'humanFinal' => 'Enterprise',
            'diff' => 'Std to Ent',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);

        $suggester = $this->createSuggester('');
        $result = $suggester->suggestTemplate();

        $this->assertStringContainsString('Insufficient stylistic data', $result);
        $this->assertStringContainsString('0 stylistic checkpoints', $result);
    }
}
