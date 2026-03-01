<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class DescriptionParserTest extends TestCase
{
    public function testParseFactualDescription(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'Textフィールドを使用',
            'humanFinal' => 'LongTextAreaに変更',
            'taskContext' => 'Salesforce項目設計',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $result = $parser->parse('SF項目設計でTextをLongTextAreaに変更。入力量の問題');

        $this->assertSame('Textフィールドを使用', $result['aiProposal']);
        $this->assertSame('LongTextAreaに変更', $result['humanFinal']);
        $this->assertSame('Salesforce項目設計', $result['taskContext']);
        $this->assertSame('factual', $result['tag']);
        $this->assertSame('estimated', $result['confidence']);
    }

    public function testParseStrategicDescription(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'Standard plan',
            'humanFinal' => 'Enterprise plan',
            'taskContext' => 'License planning',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);

        $result = $parser->parse('ライセンスをStandardからEnterpriseに変更。クライアント要件');

        $this->assertSame('strategic', $result['tag']);
    }

    public function testParseStylisticDescription(): void
    {
        $parser = $this->createParser([
            'aiProposal' => 'user_name',
            'humanFinal' => 'userName',
            'taskContext' => 'Code style',
            'tag' => 'stylistic',
            'confidence' => 'estimated',
        ]);

        $result = $parser->parse('変数名をsnake_caseからcamelCaseに統一');

        $this->assertSame('stylistic', $result['tag']);
    }

    public function testParseDefaultsOnMissingFields(): void
    {
        $parser = $this->createParser(['tag' => 'factual']);

        $result = $parser->parse('Some description');

        $this->assertSame('', $result['aiProposal']);
        $this->assertSame('', $result['humanFinal']);
        $this->assertSame('', $result['taskContext']);
        $this->assertSame('factual', $result['tag']);
        $this->assertSame('estimated', $result['confidence']);
    }

    /** @param array<string, string> $responseData */
    private function createParser(array $responseData): DescriptionParser
    {
        $responseBody = json_encode([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($responseData, JSON_THROW_ON_ERROR),
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        return new DescriptionParser(
            new Client(['handler' => HandlerStack::create($mock)]),
            'test-api-key',
            'test-model',
        );
    }
}
