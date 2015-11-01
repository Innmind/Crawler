<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CiteParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CiteParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new CiteParser(new DomCrawlerFactory);
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('citations'));

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

</body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('citations'));
    }

    public function testParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('http://foo.example.com/bar/', 'text/html'),
            new Response(
                200,
                ['Content-Type' => 'text/html'],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <base href="http://example.com/foo/" />
</head>
<body>
    <cite>foo</cite>
    <cite>bar</cite>
</body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('citations'));
        $this->assertSame(['foo', 'bar'], $r->get('citations'));
    }

    public function testName()
    {
        $this->assertSame('cite', CiteParser::getName());
    }
}
