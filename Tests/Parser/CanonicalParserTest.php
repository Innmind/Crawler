<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CanonicalParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\UrlResolver\UrlResolver;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CanonicalParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new CanonicalParser(
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
    <link rel="canonical" href="/product/42" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $return = $this->p->parse(
            $r = new HttpResource('http://xn--example.com/?product_id=42', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('canonical'));
        $this->assertSame(
            'http://xn--example.com/product/42',
            $r->get('canonical')
        );

        $response = new Response(
            200,
            ['Link' => '</product/42>; rel="canonical"']
        );

        $return = $this->p->parse(
            $r = new HttpResource('http://xn--example.com/?product_id=42', ''),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('canonical'));
        $this->assertSame(
            'http://xn--example.com/product/42',
            $r->get('canonical')
        );
    }

    public function testName()
    {
        $this->assertSame('canonical', CanonicalParser::getName());
    }
}
