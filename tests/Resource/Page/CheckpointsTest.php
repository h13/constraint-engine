<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

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
}
