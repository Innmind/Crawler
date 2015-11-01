<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CacheParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CacheParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new CacheParser;
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
            new Response(
                200,
                ['Cache-Control' => 's-maxage="10"']
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());

        $return = $this->p->parse(
            $r = new HttpResource('http://xn--example.com/', 'text/html'),
            new Response(
                200,
                ['Cache-Control' => 'max-age=10']
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());
    }

    public function testParse()
    {
        $response = new Response(
            200,
            ['Cache-Control' => 's-maxage=10']
        );

        $return = $this->p->parse(
            $r = new HttpResource('http://xn--example.com/', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('expires_at'));
        $this->assertSame(
            (new \DateTime)->modify('+10seconds')->format('Y-m-d H:i:s'),
            $r->get('expires_at')->format('Y-m-d H:i:s')
        );
    }

    public function testName()
    {
        $this->assertSame('cache', CacheParser::getName());
    }
}
