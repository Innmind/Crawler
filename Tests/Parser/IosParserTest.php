<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\IosParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class IosParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new IosParser(new DomCrawlerFactory);
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('ios'));

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
        $this->assertFalse($r->has('ios'));
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
        <meta name="apple-itunes-app" content="app-id=42, affiliate-data=foo, app-argument=innmind://">
    </head>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('ios'));
        $this->assertSame('innmind://', $r->get('ios'));
    }

    public function testName()
    {
        $this->assertSame('ios', IosParser::getName());
    }
}
