<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\ImagesParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class ImagesParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new ImagesParser(
            new UrlResolver([]),
            new DomCrawlerFactory
        );
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());

        $return = $this->p->parse(
            $r = new HttpResource('http://xn--example.com/', 'text/html'),
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
</head>
<body>
    <figure>foo</figure>
    <figure><figcaption>bar</figcaption></figure>
    <figure>
        <img src="foo.png" alt="baz" />
        <figcaption>bar</figcaption>
    </figure>
    <figure>
        <img src="bar.png" alt="baz" />
    </figure>
    <img src="foo.png" alt="foo" />
    <img src="baz.png" alt="foobar" />
</body>
</html>
HTML
            )
        );

        $return = $this->p->parse(
            $r = new HttpResource('http://xn--example.com/', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('images'));
        $this->assertSame(
            [
                ['http://xn--example.com/foo.png', 'bar'],
                ['http://xn--example.com/bar.png', 'baz'],
                ['http://xn--example.com/baz.png', 'foobar'],
            ],
            $r->get('images')
        );

        $r->set('base', 'http://xn--example.com/dir/');
        $this->p->parse($r, $response, new Stopwatch);

        $this->assertSame(
            [
                ['http://xn--example.com/dir/foo.png', 'bar'],
                ['http://xn--example.com/dir/bar.png', 'baz'],
                ['http://xn--example.com/dir/baz.png', 'foobar'],
            ],
            $r->get('images')
        );
    }

    public function testName()
    {
        $this->assertSame('images', ImagesParser::getName());
    }
}
