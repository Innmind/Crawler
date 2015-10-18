<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\RssParser;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class RssParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new RssParser(
            new UrlResolver([]),
            new DomCrawlerFactory
        );
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());

        $return = $this->p->parse(
            $r = new Resource('http://xn--example.com/', 'text/html'),
            new Response(
                200,
                ['Content-Type' => 'text/html'],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>

</body>
</html>
HTML
                )
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
            ['Content-Type' => 'text/html'],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <link rel="alternate" type="application/rss+xml" href="rss" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $r = new Resource('http://xn--example.com/', 'text/html');
        $r->set('base', 'http://xn--example.com/foo/');
        $return = $this->p->parse($r, $response, new Stopwatch);

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('rss'));
        $this->assertSame('http://xn--example.com/foo/rss', $r->get('rss'));
    }

    public function testName()
    {
        $this->assertSame('rss', RssParser::getName());
    }
}
