<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ConstraintEngine\App\Injector;
use ConstraintEngine\App\Mcp\CheckpointRecorder;
use ConstraintEngine\App\Mcp\PatternViewer;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

$context = $argv[1] ?? 'app';
$injector = Injector::getInstance($context);

$recorder = $injector->getInstance(CheckpointRecorder::class);
$viewer = $injector->getInstance(PatternViewer::class);

$server = Server::builder()
    ->setServerInfo('constraint-engine', '0.1.0')
    ->addTool([$recorder, 'recordCheckpoint'], 'record_checkpoint')
    ->addTool([$viewer, 'showPattern'], 'show_pattern')
    ->build();

$server->run(new StdioTransport());
