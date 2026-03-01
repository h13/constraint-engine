<?php

declare(strict_types=1);

namespace ConstraintEngine\App;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

abstract class ResourceTestCase extends TestCase
{
    protected ResourceInterface $resource;
    protected ExtendedPdoInterface $pdo;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('app', new TestModule());
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $pdo = $this->pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $sql = file_get_contents(__DIR__ . '/../var/sql/sqlite/create_checkpoint.sql');
        if ($sql === false) {
            return;
        }

        $pdo->exec($sql);
    }
}
