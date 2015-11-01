<?php

namespace Innmind\Crawler\Test;

use Innmind\Crawler\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $r = new Request('http://example.com/', ['Accept' => 'application/json']);

        $this->assertSame('http://example.com/', $r->getUrl());
        $this->assertSame(['Accept' => 'application/json'], $r->getHeaders());
    }
}
