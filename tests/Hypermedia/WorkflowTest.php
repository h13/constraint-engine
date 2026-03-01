<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Hypermedia;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\Injector;
use ConstraintEngine\App\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\InjectorInterface;

class WorkflowTest extends TestCase
{
    protected ResourceInterface $resource;
    protected InjectorInterface $injector;

    protected function setUp(): void
    {
        $this->injector = Injector::getOverrideInstance('app', new TestModule());
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
    }

    public function testIndex(): ResourceObject
    {
        $index = $this->resource->get('/index');
        $this->assertSame(200, $index->code);

        return $index;
    }
}
