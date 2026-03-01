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
}
