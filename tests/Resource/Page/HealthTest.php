<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;
use PDOException;

use function assert;

class HealthTest extends ResourceTestCase
{
    public function testOnGet(): void
    {
        $ro = $this->resource->get('page://self/health');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame('ok', $ro->body['status']);
        $this->assertSame('connected', $ro->body['db']);
    }

    public function testOnGetReturns503WhenDatabaseFails(): void
    {
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->method('fetchValue')->willThrowException(new PDOException('connection refused'));

        $health = new Health($pdo);
        $health->onGet();

        $this->assertSame(503, $health->code);
        $this->assertSame('error', $health->body['status']);
        $this->assertSame('disconnected', $health->body['db']);
    }
}
