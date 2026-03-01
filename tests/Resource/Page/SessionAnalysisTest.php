<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class SessionAnalysisTest extends ResourceTestCase
{
    public function testOnGetNotFound(): void
    {
        $ro = $this->resource->get('page://self/session-analysis', ['sessionId' => 'nonexistent']);
        assert($ro instanceof ResourceObject);
        $this->assertSame(404, $ro->code);
    }

    public function testOnGetWithData(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'sess-analysis',
            'taskContext' => 'テスト分析',
            'aiProposal' => 'A1',
            'humanFinal' => 'B1',
            'diff' => 'A1→B1',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'sess-analysis',
            'taskContext' => 'テスト分析',
            'aiProposal' => 'A2',
            'humanFinal' => 'B2',
            'diff' => 'A2→B2',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'sess-analysis',
            'taskContext' => 'テスト分析',
            'aiProposal' => 'A3',
            'humanFinal' => 'B3',
            'diff' => 'A3→B3',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);

        $ro = $this->resource->get('page://self/session-analysis', ['sessionId' => 'sess-analysis']);
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame('sess-analysis', $ro->body['sessionId']);
        $this->assertSame(3, $ro->body['checkpointCount']);
        $this->assertSame(2, $ro->body['factualCount']);
        $this->assertSame(1, $ro->body['strategicCount']);
        $this->assertSame(0, $ro->body['stylisticCount']);
        $this->assertCount(3, $ro->body['checkpoints']);
    }
}
