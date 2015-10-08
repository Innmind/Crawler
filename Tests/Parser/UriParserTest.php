<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\UriParser;
use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;

class UriParserTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $this->assertSame('uri', UriParser::getName());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(ParserInterface::class, new UriParser);
    }

    public function testParse()
    {
        $p = new UriParser;
        $url = 'http://foo.xn--example.com/foo?bar=baz#fragment';
        $response = new Response(200);
        $response->setEffectiveUrl($url);

        $resource = $p->parse(
            new Resource($url, 'text/html'),
            $response,
            $s = new Stopwatch
        );

        $this->assertSame('http', $resource->get('scheme'));
        $this->assertSame('foo.xn--example.com', $resource->get('host'));
        $this->assertSame('xn--example.com', $resource->get('domain'));
        $this->assertSame('com', $resource->get('tld'));
        $this->assertSame(null, $resource->get('port'));
        $this->assertSame('/foo', $resource->get('path'));
        $this->assertSame('bar=baz', $resource->get('query'));
        $this->assertFalse($resource->has('fragment'));
    }
}
