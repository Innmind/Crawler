<?php

namespace Innmind\Crawler\Tests;

use Innmind\Crawler\Resource;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testUrl()
    {
        $r = new Resource('http://example.com/', '');

        $this->assertSame('http://example.com/', $r->getUrl());
    }

    public function testContentType()
    {
        $r = new Resource('', 'application/json');

        $this->assertSame('application/json', $r->getContentType());
    }

    public function testSet()
    {
        $r = new Resource('', '');

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
        $r = new Resource('http://example.com/', '');

        $r->get('foo');
    }
}
