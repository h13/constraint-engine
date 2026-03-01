<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class TeamDashboardTest extends ResourceTestCase
{
    public function testOnGetEmpty(): void
    {
        $ro = $this->resource->get('page://self/team-dashboard');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('members', $ro->body);
        $this->assertSame([], $ro->body['members']);
    }

    public function testOnGetWithMultipleUsers(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'team-001',
            'taskContext' => 'SF設計',
            'aiProposal' => 'Text',
            'humanFinal' => 'LongTextArea',
            'diff' => 'Text→LTA',
            'tag' => 'factual',
            'confidence' => 'estimated',
            'userId' => 'alice',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'team-001',
            'taskContext' => 'SF設計',
            'aiProposal' => 'Standard',
            'humanFinal' => 'Enterprise',
            'diff' => 'Std→Ent',
            'tag' => 'strategic',
            'confidence' => 'estimated',
            'userId' => 'alice',
        ]);
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'team-002',
            'taskContext' => 'UI設計',
            'aiProposal' => '青',
            'humanFinal' => '緑',
            'diff' => '青→緑',
            'tag' => 'stylistic',
            'confidence' => 'estimated',
            'userId' => 'bob',
        ]);

        $ro = $this->resource->get('page://self/team-dashboard');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertCount(2, $ro->body['members']);

        $alice = null;
        $bob = null;
        foreach ($ro->body['members'] as $member) {
            if ($member['user_id'] === 'alice') {
                $alice = $member;
            }

            if ($member['user_id'] !== 'bob') {
                continue;
            }

            $bob = $member;
        }

        $this->assertNotNull($alice);
        $this->assertEquals(2, $alice['checkpoint_count']);
        $this->assertEquals(1, $alice['factual_count']);
        $this->assertEquals(1, $alice['strategic_count']);

        $this->assertNotNull($bob);
        $this->assertEquals(1, $bob['checkpoint_count']);
        $this->assertEquals(1, $bob['stylistic_count']);
    }
}
