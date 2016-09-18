<?php
declare(strict_types = 1);

namespace Tests\Innmind\Crawler;

use Innmind\Crawler\Request;
use Innmind\Url\Url;
use Innmind\Http\Message\Method;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Map;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $request = new Request($url = Url::fromString('http://example.com'));

        $this->assertSame($url, $request->url());
        $this->assertInstanceOf(Method::class, $request->method());
        $this->assertSame('GET', (string) $request->method());
        $this->assertFalse($request->hasHeaders());
        $this->assertFalse($request->hasPayload());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenNoHostSpecified()
    {
        new Request(Url::fromString('/foo'));
    }

    public function testWithMethod()
    {
        $request = new Request(Url::fromString('htt://example.com'));
        $request2 = $request->withMethod($method = new Method('POST'));

        $this->assertInstanceOf(Request::class, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertSame($request->url(), $request2->url());
        $this->assertNotSame($request->method(), $request2->method());
        $this->assertSame($method, $request2->method());
    }

    public function testWithHeaders()
    {
        $request = new Request(Url::fromString('http://example.com'));
        $request2 = $request->withHeaders(
            $headers = (new Map('string', 'string'))
                ->put('Accept', 'application/json')
        );

        $this->assertInstanceOf(Request::class, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertSame($request->url(), $request2->url());
        $this->assertSame($request->method(), $request2->method());
        $this->assertFalse($request->hasHeaders());
        $this->assertTrue($request2->hasHeaders());
        $this->assertSame($headers, $request2->headers());
    }

    /**
     * @expectedException Innmind\Crawler\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidHeaderMap()
    {
        (new Request(Url::fromString('http://example.com')))
            ->withHeaders(new Map('int', 'int'));
    }

    public function testWithPayload()
    {
        $request = new Request(Url::fromString('http://example.com'));
        $request2 = $request->withPayload($payload = new StringStream('foo'));

        $this->assertInstanceOf(Request::class, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertSame($request->url(), $request2->url());
        $this->assertSame($request->method(), $request2->method());
        $this->assertFalse($request->hasPayload());
        $this->assertTrue($request2->hasPayload());
        $this->assertSame($payload, $request2->payload());
    }
}
