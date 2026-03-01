<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Module;

use Be\Framework\Module\BeModule;
use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Package\Provide\Router\AuraRouterModule;
use ConstraintEngine\App\Semantic\Tag;
use Koriym\EnvJson\EnvJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\Scope;
use Ray\MediaQuery\MediaQuerySqlModule;

use function dirname;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        (new EnvJson())->load(dirname(__DIR__, 2));
        $appDir = dirname(__DIR__, 2);
        $dsn = $_ENV['DB_DSN'] ?? 'sqlite:' . $appDir . '/var/db/constraint_engine.sqlite3';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        $this->install(new AuraSqlModule($dsn, $user, $pass));
        $this->install(new MediaQuerySqlModule($appDir . '/src/Query', $appDir . '/var/sql'));
        $this->install(new AuraRouterModule($appDir . '/var/conf/aura.route.php'));
        $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
        $this->install(new BeModule('ConstraintEngine\App\Semantic'));
        $this->bind(Tag::class);
        $this->install(new McpModule());
        $this->install(new PackageModule());
    }
}
