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
        $this->install(new AuraSqlModule('sqlite::memory:'));
        $this->install(new BeModule('ConstraintEngine\App\Semantic'));
    }
}
