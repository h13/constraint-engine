<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use ConstraintEngine\App\Injector;
use ConstraintEngine\App\TestModule;
use PHPUnit\Framework\TestCase;

use function assert;
use function file_get_contents;

class RecallTrackerTest extends TestCase
{
    private RecallTracker $tracker;
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('app', new TestModule());
        $this->tracker = $injector->getInstance(RecallTracker::class);
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $sql = file_get_contents(__DIR__ . '/../../var/sql/sqlite/create_checkpoint.sql');
        if ($sql === false) {
            $this->fail('Schema file not found: var/sql/sqlite/create_checkpoint.sql');
        }

        $pdo->exec($sql);
    }

    private function createCheckpoint(): int
    {
        $ro = $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'テスト',
            'aiProposal' => '提案A',
            'humanFinal' => '最終B',
            'diff' => 'A→B',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        assert(isset($ro->body['checkpointId']));

        return (int) $ro->body['checkpointId'];
    }

    public function testRecordRecall(): void
    {
        $id = $this->createCheckpoint();
        $result = $this->tracker->recordRecall($id, 'Used in project decision');
        $this->assertStringContainsString('Recall recorded', $result);
        $this->assertStringContainsString((string) $id, $result);
    }

    public function testRecordDiscovery(): void
    {
        $id = $this->createCheckpoint();
        $result = $this->tracker->recordDiscovery($id, 'Found unexpected trend');
        $this->assertStringContainsString('Discovery recorded', $result);
    }

    public function testRecordFriction(): void
    {
        $id = $this->createCheckpoint();
        $result = $this->tracker->recordFriction($id, 'Too slow');
        $this->assertStringContainsString('Friction recorded', $result);
    }

    public function testRecordRecallNotFound(): void
    {
        $result = $this->tracker->recordRecall(99999);
        $this->assertStringContainsString('not found', $result);
    }

    public function testShowGoNoGoPending(): void
    {
        $result = $this->tracker->showGoNoGo();
        $this->assertStringContainsString('PENDING', $result);
    }

    public function testShowGoNoGoReachesGo(): void
    {
        $id = $this->createCheckpoint();
        $this->tracker->recordRecall($id, 'recall 1');
        $this->tracker->recordRecall($id, 'recall 2');
        $this->tracker->recordRecall($id, 'recall 3');
        $this->tracker->recordDiscovery($id, 'discovery 1');

        $result = $this->tracker->showGoNoGo();
        $this->assertStringContainsString('GO', $result);
        $this->assertStringNotContainsString('NO_GO', $result);
    }

    public function testShowGoNoGoExcessiveFriction(): void
    {
        $id = $this->createCheckpoint();
        $this->tracker->recordFriction($id, 'friction 1');
        $this->tracker->recordFriction($id, 'friction 2');
        $this->tracker->recordFriction($id, 'friction 3');

        $result = $this->tracker->showGoNoGo();
        $this->assertStringContainsString('NO_GO', $result);
    }
}
