<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use BEAR\Resource\ResourceObject;
use ConstraintEngine\App\ResourceTestCase;

use function assert;

class GoNoGoTest extends ResourceTestCase
{
    public function testOnGetEmpty(): void
    {
        $ro = $this->resource->get('page://self/go-no-go');
        assert($ro instanceof ResourceObject);
        $this->assertSame(200, $ro->code);
        $this->assertSame(0, $ro->body['recall_count']);
        $this->assertSame(3, $ro->body['recall_target']);
        $this->assertSame(0, $ro->body['discovery_count']);
        $this->assertSame(1, $ro->body['discovery_target']);
        $this->assertSame(0, $ro->body['friction_count']);
        $this->assertSame(2, $ro->body['friction_limit']);
        $this->assertSame('pending', $ro->body['verdict']);
    }
}
