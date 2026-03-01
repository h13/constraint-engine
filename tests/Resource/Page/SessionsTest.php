<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class SessionsTest extends ResourceTestCase
{
    public function testOnGetEmpty(): void
    {
        $ro = $this->resource->get('page://self/sessions');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('sessions', $ro->body);
        $this->assertSame([], $ro->body['sessions']);
    }

    public function testOnGetWithData(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'sess-001',
            'taskContext' => 'SF設計',
            'aiProposal' => 'Text',
            'humanFinal' => 'LongTextArea',
            'diff' => 'Text→LTA',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'sess-001',
            'taskContext' => 'SF設計',
            'aiProposal' => 'Standard',
            'humanFinal' => 'Enterprise',
            'diff' => 'Std→Ent',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'sess-002',
            'taskContext' => 'UI設計',
            'aiProposal' => '青',
            'humanFinal' => '緑',
            'diff' => '青→緑',
            'tag' => 'stylistic',
            'confidence' => 'estimated',
        ]);

        $ro = $this->resource->get('page://self/sessions');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertCount(2, $ro->body['sessions']);

        $sessions = $ro->body['sessions'];
        $sess001 = null;
        foreach ($sessions as $s) {
            if ($s['sessionId'] !== 'sess-001') {
                continue;
            }

            $sess001 = $s;
        }

        $this->assertNotNull($sess001);
        $this->assertEquals(2, $sess001['checkpointCount']);
        $this->assertEquals(1, $sess001['factualCount']);
        $this->assertEquals(1, $sess001['strategicCount']);
    }
}
