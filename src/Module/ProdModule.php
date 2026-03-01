<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Module;

use BEAR\Package\Provide\Error\ErrorPageFactoryInterface;
use BEAR\Package\Provide\Error\ProdVndErrorPageFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class ProdModule extends AbstractModule
{
    protected function configure(): void
    {
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
        $this->bind(ErrorPageFactoryInterface::class)->to(ProdVndErrorPageFactory::class);
    }
}
