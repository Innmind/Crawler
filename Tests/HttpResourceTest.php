<?php

namespace Innmind\Crawler\Tests;

use Innmind\Crawler\HttpResource;

class HttpResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testUrl()
    {
        $r = new HttpResource('http://example.com/', '');

        $this->assertSame('http://example.com/', $r->getUrl());
    }

    public function testContentType()
    {
        $r = new HttpResource('', 'application/json');

        $this->assertSame('application/json', $r->getContentType());
    }

    public function testSet()
    {
        $r = new HttpResource('', '');

        $this->assertFalse($r->has('foo'));
        $this->assertSame($r, $r->set('foo', 'bar'));
        $this->assertTrue($r->has('foo'));
        $this->assertSame('bar', $r->get('foo'));
        $this->assertSame(['foo'], $r->keys());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown attribute "foo" for resource at "http://example.com/"
     */
    public function testThrowIfTryingToAccessUnknownAttribute()
    {
        $r = new HttpResource('http://example.com/', '');

        $r->get('foo');
    }
}
