<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class CheckpointTest extends ResourceTestCase
{
    public function testOnGet(): void
    {
        $posted = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'Salesforce項目設計',
            'aiProposal' => 'Textフィールドを使用',
            'humanFinal' => 'LongTextAreaに変更',
            'diff' => 'Text→LongTextArea',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        assert($posted instanceof ResourceObject);
        $id = $posted->body['id'];

        $ro = $this->resource->get('page://self/checkpoint', ['id' => $id]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame('factual', $ro->body['tag']);
    }

    public function testOnGetNotFound(): void
    {
        $ro = $this->resource->get('page://self/checkpoint', ['id' => 99999]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(404, $ro->code);
    }

    public function testOnPut(): void
    {
        $posted = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'Salesforce項目設計',
            'aiProposal' => 'Textフィールドを使用',
            'humanFinal' => 'LongTextAreaに変更',
            'diff' => 'Text→LongTextArea',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        assert($posted instanceof ResourceObject);
        $id = $posted->body['id'];

        $ro = $this->resource->put('page://self/checkpoint', ['id' => $id, 'tag' => 'strategic']);
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame('strategic', $ro->body['tag']);
    }

    public function testOnPutNotFound(): void
    {
        $ro = $this->resource->put('page://self/checkpoint', ['id' => 99999, 'tag' => 'strategic']);
        assert($ro instanceof ResourceObject);
        $this->assertSame(404, $ro->code);
    }

    public function testOnPutInvalidTag(): void
    {
        $posted = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'テスト',
            'aiProposal' => '提案A',
            'humanFinal' => '最終B',
            'diff' => 'A→B',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        assert($posted instanceof ResourceObject);
        $id = $posted->body['id'];

        $ro = $this->resource->put('page://self/checkpoint', ['id' => $id, 'tag' => 'invalid']);
        assert($ro instanceof ResourceObject);
        $this->assertSame(422, $ro->code);
        $this->assertArrayHasKey('errors', $ro->body);
    }
}
