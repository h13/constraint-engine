<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Sunday\Provide\Error\VndErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Ray\Di\Scope;

final class ProdModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new AppModule());
        $this->bind(LoggerInterface::class)->toConstructor(
            Logger::class,
            [
                'name' => 'logger_name',
                'handlers' => 'logger_handlers',
            ],
        )->in(Scope::SINGLETON);
        $this->bind()->annotatedWith('logger_name')->toInstance('constraint-engine');
        $this->bind()->annotatedWith('logger_handlers')->toInstance([
            new StreamHandler('php://stderr'),
        ]);
        $this->bind(ThrowableHandlerInterface::class)->to(VndErrorHandler::class);
    }
}
