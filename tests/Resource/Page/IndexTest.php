<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class IndexTest extends ResourceTestCase
{
    public function testOnGet(): void
    {
        $ro = $this->resource->get('page://self/index');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame('Constraint Engine', $ro->body['name']);
        $this->assertArrayHasKey('description', $ro->body);
    }
}
