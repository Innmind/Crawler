<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CiteParser;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CiteParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new CiteParser;

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('citations'));

        $return = $p->parse(
            $r = new Resource('', 'text/html'),
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
        $this->assertFalse($r->has('citations'));
    }

    public function testParse()
    {
        $p = new CiteParser;

        $return = $p->parse(
            $r = new Resource('http://foo.example.com/bar/', 'text/html'),
            new Response(
                200,
                [],
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
