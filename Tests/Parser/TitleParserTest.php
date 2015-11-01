<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\TitleParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class TitleParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new TitleParser(new DomCrawlerFactory);
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('title'));

        $return = $this->p->parse(
            $r = new HttpResource('', 'text/html'),
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
    <article></article>
</body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('title'));
    }

    public function testParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'text/html'),
            new Response(
                200,
                ['Content-Type' => 'text/html'],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <h1> Foo </h1>
    </body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('title'));
        $this->assertSame('Foo', $r->get('title'));

        $this->p->parse(
            $r = new HttpResource('', 'text/html'),
            new Response(
                200,
                ['Content-Type' => 'text/html'],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title> Bar </title>
    </head>
    <body>
    </body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertTrue($r->has('title'));
        $this->assertSame('Bar', $r->get('title'));
    }

    public function testName()
    {
        $this->assertSame('title', TitleParser::getName());
    }
}
