<?php

namespace Innmind\Crawler\Tests;

use Innmind\Crawler\Parser;
use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\Parser\UriParser;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $this->assertSame('parser', Parser::getName());
    }

    public function testInstance()
    {
        $this->assertInstanceOf(ParserInterface::class, new Parser);
    }

    public function testParse()
    {
        $p = new Parser;
        $p->addPass(new UriParser);
        $url = 'http://foo.xn--example.com/foo?bar=baz#fragment';
        $response = new Response(200);
        $response->setEffectiveUrl($url);

        $resource = $p->parse(
            new HttpResource($url, 'text/html'),
            $response,
            $s = new Stopwatch
        );

        $this->assertTrue(!empty($s->getEvent('uri')));
    }
}
