<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ConstraintEngine\App\Injector;
use ConstraintEngine\App\Mcp\CheckpointRecorder;
use ConstraintEngine\App\Mcp\ImprovementSuggester;
use ConstraintEngine\App\Mcp\InsightGenerator;
use ConstraintEngine\App\Mcp\PatternViewer;
use ConstraintEngine\App\Mcp\QuickRecorder;
use ConstraintEngine\App\Mcp\RecallTracker;
use ConstraintEngine\App\Mcp\SessionAnalyzer;
use ConstraintEngine\App\Mcp\SessionManager;
use ConstraintEngine\App\Mcp\TemplateSuggester;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

$context = $argv[1] ?? 'app';
$injector = Injector::getInstance($context);

$sessionManager = $injector->getInstance(SessionManager::class);
$recorder = $injector->getInstance(CheckpointRecorder::class);
$viewer = $injector->getInstance(PatternViewer::class);
$quickRecorder = $injector->getInstance(QuickRecorder::class);
$sessionAnalyzer = $injector->getInstance(SessionAnalyzer::class);
$insightGenerator = $injector->getInstance(InsightGenerator::class);
$improvementSuggester = $injector->getInstance(ImprovementSuggester::class);
$recallTracker = $injector->getInstance(RecallTracker::class);
$templateSuggester = $injector->getInstance(TemplateSuggester::class);

$server = Server::builder()
    ->setServerInfo('constraint-engine', '0.4.8')
    ->addTool([$recorder, 'recordCheckpoint'], 'record_checkpoint')
    ->addTool([$quickRecorder, 'quickRecord'], 'quick_record')
    ->addTool([$viewer, 'showPattern'], 'show_pattern')
    ->addTool([$viewer, 'comparePeriods'], 'compare_periods')
    ->addTool([$viewer, 'showImprovementRate'], 'show_improvement_rate')
    ->addTool([$recallTracker, 'recordRecall'], 'record_recall')
    ->addTool([$recallTracker, 'recordDiscovery'], 'record_discovery')
    ->addTool([$recallTracker, 'recordFriction'], 'record_friction')
    ->addTool([$recallTracker, 'showGoNoGo'], 'show_go_no_go')
    ->addTool([$sessionManager, 'startSession'], 'start_session')
    ->addTool([$sessionManager, 'endSession'], 'end_session')
    ->addTool([$sessionAnalyzer, 'analyzeSession'], 'analyze_session')
    ->addTool([$insightGenerator, 'generateInsights'], 'generate_insights')
    ->addTool([$improvementSuggester, 'suggestImprovements'], 'suggest_improvements')
    ->addTool([$templateSuggester, 'suggestTemplate'], 'suggest_template')
    ->build();

$server->run(new StdioTransport());
