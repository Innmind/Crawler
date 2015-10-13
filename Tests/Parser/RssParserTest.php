<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\RssParser;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class RssParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new RssParser(new UrlResolver([]));

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());

        $return = $p->parse(
            $r = new Resource('http://xn--example.com/', 'text/html'),
            new Response(
                200,
                [],
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
        $p = new RssParser(new UrlResolver([]));
        $response = new Response(
            200,
            [],
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
        $return = $p->parse($r, $response, new Stopwatch);

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('rss'));
        $this->assertSame('http://xn--example.com/foo/rss', $r->get('rss'));
    }

    public function testName()
    {
        $this->assertSame('rss', RssParser::getName());
    }
}
