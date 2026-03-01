<?php

declare(strict_types=1);

/** @var \Aura\Router\Map $map */
$map->route('/checkpoints', '/checkpoints');
$map->route('/checkpoint', '/checkpoints/{id}')->tokens(['id' => '\d+']);
$map->route('/pattern-dashboard', '/pattern-dashboard');
$map->route('/go-no-go', '/go-no-go');
$map->route('/health', '/health');
