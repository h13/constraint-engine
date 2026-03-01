<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ConstraintEngine\App\Injector;
use ConstraintEngine\App\Mcp\CheckpointRecorder;
use ConstraintEngine\App\Mcp\PatternViewer;
use ConstraintEngine\App\Mcp\RecallTracker;
use ConstraintEngine\App\Mcp\SessionManager;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

$context = $argv[1] ?? 'app';
$injector = Injector::getInstance($context);

$sessionManager = $injector->getInstance(SessionManager::class);
$recorder = $injector->getInstance(CheckpointRecorder::class);
$viewer = $injector->getInstance(PatternViewer::class);
$recallTracker = $injector->getInstance(RecallTracker::class);

$server = Server::builder()
    ->setServerInfo('constraint-engine', '0.2.0')
    ->addTool([$recorder, 'recordCheckpoint'], 'record_checkpoint')
    ->addTool([$viewer, 'showPattern'], 'show_pattern')
    ->addTool([$recallTracker, 'recordRecall'], 'record_recall')
    ->addTool([$recallTracker, 'recordDiscovery'], 'record_discovery')
    ->addTool([$recallTracker, 'recordFriction'], 'record_friction')
    ->addTool([$recallTracker, 'showGoNoGo'], 'show_go_no_go')
    ->addTool([$sessionManager, 'startSession'], 'start_session')
    ->addTool([$sessionManager, 'endSession'], 'end_session')
    ->build();

$server->run(new StdioTransport());
