<?php

declare(strict_types=1);

namespace ConstraintEngine\App;

use Be\Framework\Module\BeModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;

final class TestModule extends AbstractModule
{
    protected function configure(): void
    {
        $dsn = $_ENV['DB_DSN'] ?? 'pgsql:host=localhost;dbname=constraint_engine_test';
        $user = $_ENV['DB_USER'] ?? 'app';
        $pass = $_ENV['DB_PASS'] ?? 'secret';
        $this->install(new AuraSqlModule($dsn, $user, $pass));
        $this->install(new BeModule('ConstraintEngine\App\Semantic'));
    }
}
