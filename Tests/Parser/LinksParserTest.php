<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\LinksParser;
use Innmind\Crawler\Resource;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class LinksParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new LinksParser(new UrlResolver([]));

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
        $p = new LinksParser(new UrlResolver([]));
        $response = new Response(
            200,
            [],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <link rel="first" href="/" />
    <link rel="last" href="/42" />
    <link rel="next" href="/3" />
    <link rel="previous" href="/1" />
</head>
<body>
<a href="#anchor"></a>
<a href="http://sub.xn--example.com/"></a>
<a href="relative?foo=bar#frag"></a>
</body>
</html>
HTML
            )
        );

        $r = new Resource('http://xn--example.com/2', 'text/html');
        $r->set('base', 'http://xn--example.com/foo/');
        $return = $p->parse($r, $response, new Stopwatch);

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('links'));
        $this->assertSame(
            [
                'http://xn--example.com/',
                'http://xn--example.com/42',
                'http://xn--example.com/3',
                'http://xn--example.com/1',
                'http://sub.xn--example.com/',
                'http://xn--example.com/foo/relative?foo=bar#frag',
            ],
            $r->get('links')
        );
    }

    public function testName()
    {
        $this->assertSame('links', LinksParser::getName());
    }
}
