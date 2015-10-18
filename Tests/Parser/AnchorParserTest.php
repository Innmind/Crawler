<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\AnchorParser;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class AnchorParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new AnchorParser(new DomCrawlerFactory);
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
    <a id="foo"></a>
    <a hrel="http://example.com"></a>
    <a href="foo"></a>
    <a href="#bar"></a>
    <a href="#baz"></a>
    <a href="#baz"></a>
    <a href="#"></a>
</body>
</html>
HTML
            )
        );

        $return = $this->p->parse(
            $r = new Resource('', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('anchors'));
        $this->assertSame(['bar', 'baz'], $r->get('anchors'));
    }

    public function testName()
    {
        $this->assertSame('anchor', AnchorParser::getName());
    }
}
