<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class GoNoGoTest extends ResourceTestCase
{
    public function testOnGetEmpty(): void
    {
        $ro = $this->resource->get('page://self/go-no-go');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame(0, $ro->body['recall_count']);
        $this->assertSame(3, $ro->body['recall_target']);
        $this->assertSame(0, $ro->body['discovery_count']);
        $this->assertSame(1, $ro->body['discovery_target']);
        $this->assertSame(0, $ro->body['friction_count']);
        $this->assertSame(2, $ro->body['friction_limit']);
        $this->assertSame('pending', $ro->body['verdict']);
    }

    public function testOnGetGoVerdict(): void
    {
        $checkpointId = $this->createCheckpoint();
        $this->insertRecall($checkpointId, 'recall', 3);
        $this->insertRecall($checkpointId, 'discovery', 1);

        $ro = $this->resource->get('page://self/go-no-go');
        assert($ro instanceof ResourceObject);
        $this->assertSame('go', $ro->body['verdict']);
        $this->assertSame(3, $ro->body['recall_count']);
        $this->assertSame(1, $ro->body['discovery_count']);
        $this->assertSame(0, $ro->body['friction_count']);
    }

    public function testOnGetNoGoVerdict(): void
    {
        $checkpointId = $this->createCheckpoint();
        $this->insertRecall($checkpointId, 'recall', 5);
        $this->insertRecall($checkpointId, 'discovery', 2);
        $this->insertRecall($checkpointId, 'friction', 3);

        $ro = $this->resource->get('page://self/go-no-go');
        assert($ro instanceof ResourceObject);
        $this->assertSame('no_go', $ro->body['verdict']);
    }

    public function testOnGetPendingVerdict(): void
    {
        $checkpointId = $this->createCheckpoint();
        $this->insertRecall($checkpointId, 'recall', 2);
        $this->insertRecall($checkpointId, 'discovery', 1);

        $ro = $this->resource->get('page://self/go-no-go');
        assert($ro instanceof ResourceObject);
        $this->assertSame('pending', $ro->body['verdict']);
    }

    private function createCheckpoint(): int
    {
        $this->pdo->perform(
            'INSERT INTO checkpoint (session_id, task_context, ai_proposal, human_final, diff, tag, confidence) VALUES (:session_id, :task_context, :ai_proposal, :human_final, :diff, :tag, :confidence)',
            [
                'session_id' => 'go-no-go-test',
                'task_context' => 'テスト',
                'ai_proposal' => 'A',
                'human_final' => 'B',
                'diff' => 'A→B',
                'tag' => 'factual',
                'confidence' => 'estimated',
            ],
        );

        return (int) $this->pdo->lastInsertId();
    }

    private function insertRecall(int $checkpointId, string $type, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->pdo->perform(
                'INSERT INTO checkpoint_recall (checkpoint_id, type, note) VALUES (:checkpoint_id, :type, :note)',
                [
                    'checkpoint_id' => $checkpointId,
                    'type' => $type,
                    'note' => "test {$type} #{$i}",
                ],
            );
        }
    }
}
