<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

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
}
