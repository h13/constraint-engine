<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;
use function is_array;

class CheckpointsTest extends ResourceTestCase
{
    public function testOnGetEmpty(): void
    {
        $ro = $this->resource->get('page://self/checkpoints');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame([], $ro->body);
    }

    public function testOnPost(): void
    {
        $ro = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'Salesforce項目設計',
            'aiProposal' => 'Textフィールドを使用',
            'humanFinal' => 'LongTextAreaに変更',
            'diff' => 'Text→LongTextArea',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(201, $ro->code);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertMatchesRegularExpression('#/checkpoints/\d+#', $ro->headers['Location']);
    }

    public function testOnPostThenGet(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'Salesforce項目設計',
            'aiProposal' => 'Textフィールドを使用',
            'humanFinal' => 'LongTextAreaに変更',
            'diff' => 'Text→LongTextArea',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $ro = $this->resource->get('page://self/checkpoints');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertCount(1, $ro->body);
        $this->assertSame('factual', $ro->body[0]['tag']);
    }

    public function testOnPostInvalidTag(): void
    {
        $ro = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'テスト',
            'aiProposal' => '提案A',
            'humanFinal' => '最終B',
            'diff' => 'A→B',
            'tag' => 'invalid-tag',
            'confidence' => 'estimated',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(422, $ro->code);
        $this->assertArrayHasKey('errors', $ro->body);
        assert(is_array($ro->body['errors']));
        $this->assertNotEmpty($ro->body['errors']);
    }

    public function testOnPostInvalidConfidence(): void
    {
        $ro = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'テスト',
            'aiProposal' => '提案A',
            'humanFinal' => '最終B',
            'diff' => 'A→B',
            'tag' => 'factual',
            'confidence' => 'unknown',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(422, $ro->code);
        $this->assertArrayHasKey('errors', $ro->body);
        assert(is_array($ro->body['errors']));
        $this->assertNotEmpty($ro->body['errors']);
    }

    public function testOnPostEmptySessionId(): void
    {
        $ro = $this->resource->post('page://self/checkpoints', [
            'sessionId' => '   ',
            'taskContext' => 'テスト',
            'aiProposal' => '提案A',
            'humanFinal' => '最終B',
            'diff' => 'A→B',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(422, $ro->code);
        $this->assertArrayHasKey('errors', $ro->body);
    }
}
