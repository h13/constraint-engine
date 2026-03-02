<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class PatternDashboardTest extends ResourceTestCase
{
    public function testOnGetEmpty(): void
    {
        $ro = $this->resource->get('page://self/pattern-dashboard');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('summary', $ro->body);
        $this->assertArrayHasKey('tagDistribution', $ro->body);
    }

    public function testOnGetWithData(): void
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
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-002',
            'taskContext' => 'UI設計',
            'aiProposal' => '青色ボタン',
            'humanFinal' => '緑色ボタン',
            'diff' => '青→緑',
            'tag' => 'stylistic',
            'confidence' => 'estimated',
        ]);
        $ro = $this->resource->get('page://self/pattern-dashboard');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertEquals(2, $ro->body['summary']['totalCheckpoints']);
        $this->assertCount(2, $ro->body['tagDistribution']);
    }

    public function testOnGetWithTrend(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'test',
            'aiProposal' => 'a',
            'humanFinal' => 'b',
            'diff' => 'a→b',
            'tag' => 'strategic',
            'confidence' => 'estimated',
        ]);
        $ro = $this->resource->get('page://self/pattern-dashboard', [
            'periodStart' => '2020-01-01',
            'periodEnd' => '2030-12-31',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertNotEmpty($ro->body['trend']);
    }

    public function testOnGetWithComparison(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'test',
            'aiProposal' => 'a',
            'humanFinal' => 'b',
            'diff' => 'a→b',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $ro = $this->resource->get('page://self/pattern-dashboard', [
            'periodStart' => '2020-01-01',
            'periodEnd' => '2030-12-31',
            'compareStart' => '2010-01-01',
            'compareEnd' => '2019-12-31',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertNotNull($ro->body['comparison']);
        $this->assertArrayHasKey('current', $ro->body['comparison']);
        $this->assertArrayHasKey('previous', $ro->body['comparison']);
    }

    public function testOnGetComparisonNullWhenPartialParams(): void
    {
        $ro = $this->resource->get('page://self/pattern-dashboard', [
            'periodStart' => '2020-01-01',
            'periodEnd' => '2030-12-31',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertNull($ro->body['comparison']);
    }

    public function testOnGetInvalidPeriodEndReturns400(): void
    {
        $ro = $this->resource->get('page://self/pattern-dashboard', [
            'periodStart' => '2026-01-01',
            'periodEnd' => 'not-a-date',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertSame(400, $ro->code);
        $this->assertArrayHasKey('error', $ro->body);
    }

    public function testOnGetFactualRateWithPeriod(): void
    {
        $this->resource->post('page://self/checkpoints', [
            'sessionId' => 'test-001',
            'taskContext' => 'test',
            'aiProposal' => 'a',
            'humanFinal' => 'b',
            'diff' => 'a→b',
            'tag' => 'factual',
            'confidence' => 'estimated',
        ]);
        $ro = $this->resource->get('page://self/pattern-dashboard', [
            'periodStart' => '2020-01-01',
            'periodEnd' => '2030-12-31',
        ]);
        assert($ro instanceof ResourceObject);
        $this->assertNotEmpty($ro->body['factualRate']);
    }
}
