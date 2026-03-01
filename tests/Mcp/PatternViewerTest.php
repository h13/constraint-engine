<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Mcp;

use ConstraintEngine\App\Query\CheckpointQueryInterface;
use PHPUnit\Framework\TestCase;

class PatternViewerTest extends TestCase
{
    public function testShowPatternNoData(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('summary')->willReturn(null);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $this->assertSame('No checkpoints recorded yet.', $viewer->showPattern());
    }

    public function testShowPatternZeroCheckpoints(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('summary')->willReturn([
            'totalCheckpoints' => 0,
            'factualCount' => 0,
            'strategicCount' => 0,
            'stylisticCount' => 0,
        ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $this->assertSame('No checkpoints recorded yet.', $viewer->showPattern());
    }

    public function testShowPatternWithData(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('summary')->willReturn([
            'totalCheckpoints' => 10,
            'factualCount' => 5,
            'strategicCount' => 3,
            'stylisticCount' => 2,
        ]);
        $query->method('tagDistribution')->willReturn([
            ['tag' => 'factual', 'count' => 5],
            ['tag' => 'strategic', 'count' => 3],
            ['tag' => 'stylistic', 'count' => 2],
        ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showPattern();

        $this->assertStringContainsString('10 checkpoints', $result);
        $this->assertStringContainsString('Factual:   5 (50.0%)', $result);
        $this->assertStringContainsString('Strategic: 3 (30.0%)', $result);
        $this->assertStringContainsString('Stylistic: 2 (20.0%)', $result);
        $this->assertStringContainsString('Distribution:', $result);
    }

    public function testComparePeriodsNoData(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('periodSummary')->willReturn(null);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->comparePeriods('2026-01-01', '2026-01-07', '2025-12-25', '2025-12-31');

        $this->assertSame('No checkpoints in either period.', $result);
    }

    public function testComparePeriodsWithData(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('periodSummary')
            ->willReturnMap([
                [
                    '2026-01-01',
                    '2026-01-08',
                    [
                        'totalCheckpoints' => 10,
                        'factualCount' => 3,
                        'strategicCount' => 5,
                        'stylisticCount' => 2,
                    ],
                ],
                [
                    '2025-12-25',
                    '2026-01-01',
                    [
                        'totalCheckpoints' => 8,
                        'factualCount' => 4,
                        'strategicCount' => 2,
                        'stylisticCount' => 2,
                    ],
                ],
            ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->comparePeriods('2026-01-01', '2026-01-07', '2025-12-25', '2025-12-31');

        $this->assertStringContainsString('Period Comparison', $result);
        $this->assertStringContainsString('2026-01-01 ~ 2026-01-07 (10 checkpoints)', $result);
        $this->assertStringContainsString('2025-12-25 ~ 2025-12-31 (8 checkpoints)', $result);
        $this->assertStringContainsString('Factual:', $result);
        $this->assertStringContainsString('pp)', $result);
    }

    public function testComparePeriodsShowsNewWhenPreviousIsZero(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('periodSummary')
            ->willReturnMap([
                [
                    '2026-01-01',
                    '2026-01-08',
                    [
                        'totalCheckpoints' => 5,
                        'factualCount' => 3,
                        'strategicCount' => 2,
                        'stylisticCount' => 0,
                    ],
                ],
                [
                    '2025-12-25',
                    '2026-01-01',
                    [
                        'totalCheckpoints' => 5,
                        'factualCount' => 0,
                        'strategicCount' => 5,
                        'stylisticCount' => 0,
                    ],
                ],
            ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->comparePeriods('2026-01-01', '2026-01-07', '2025-12-25', '2025-12-31');

        $this->assertStringContainsString('(new)', $result);
        $this->assertStringContainsString('(-)', $result);
    }

    public function testShowPatternIncludesDashboardUrl(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('summary')->willReturn([
            'totalCheckpoints' => 1,
            'factualCount' => 1,
            'strategicCount' => 0,
            'stylisticCount' => 0,
        ]);
        $query->method('tagDistribution')->willReturn([]);

        $viewer = new PatternViewer($query, 'http://example.com:9090');
        $result = $viewer->showPattern();

        $this->assertStringContainsString('http://example.com:9090/pattern-dashboard', $result);
    }

    public function testShowImprovementRateNoData(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('factualRate')->willReturn([]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showImprovementRate('2026-01-01', '2026-01-31');

        $this->assertStringContainsString('No checkpoints found', $result);
    }

    public function testShowImprovementRateDecreasing(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('factualRate')->willReturn([
            ['date' => '2026-01-01', 'total' => 5, 'factualCount' => 3, 'factualRate' => 60.0],
            ['date' => '2026-01-02', 'total' => 8, 'factualCount' => 2, 'factualRate' => 25.0],
        ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showImprovementRate('2026-01-01', '2026-01-02');

        $this->assertStringContainsString('Factual Correction Rate', $result);
        $this->assertStringContainsString('learning effect detected', $result);
    }

    public function testShowImprovementRateIncreasing(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('factualRate')->willReturn([
            ['date' => '2026-01-01', 'total' => 5, 'factualCount' => 1, 'factualRate' => 20.0],
            ['date' => '2026-01-02', 'total' => 5, 'factualCount' => 3, 'factualRate' => 60.0],
        ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showImprovementRate('2026-01-01', '2026-01-02');

        $this->assertStringContainsString('may need attention', $result);
    }

    public function testShowImprovementRateStable(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('factualRate')->willReturn([
            ['date' => '2026-01-01', 'total' => 5, 'factualCount' => 2, 'factualRate' => 40.0],
            ['date' => '2026-01-02', 'total' => 5, 'factualCount' => 2, 'factualRate' => 40.0],
        ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showImprovementRate('2026-01-01', '2026-01-02');

        $this->assertStringContainsString('Factual rate stable', $result);
    }

    public function testShowImprovementRateSingleDay(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('factualRate')->willReturn([
            ['date' => '2026-01-01', 'total' => 5, 'factualCount' => 2, 'factualRate' => 40.0],
        ]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showImprovementRate('2026-01-01', '2026-01-01');

        $this->assertStringContainsString('40.0%', $result);
        $this->assertStringNotContainsString('Improvement', $result);
        $this->assertStringNotContainsString('Note:', $result);
    }

    public function testComparePeriodsUsesDefaultDatesWhenNoArgs(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('periodSummary')->willReturn(null);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->comparePeriods();

        $this->assertSame('No checkpoints in either period.', $result);
    }

    public function testComparePeriodsUsesDefaultDatesWhenPartialArgs(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('periodSummary')->willReturn(null);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->comparePeriods(currentStart: '2026-01-01');

        $this->assertSame('No checkpoints in either period.', $result);
    }

    public function testShowImprovementRateUsesDefaultDatesWhenNoArgs(): void
    {
        $query = $this->createMock(CheckpointQueryInterface::class);
        $query->method('factualRate')->willReturn([]);

        $viewer = new PatternViewer($query, 'http://test:8080');
        $result = $viewer->showImprovementRate();

        $this->assertStringContainsString('No checkpoints found', $result);
    }
}
