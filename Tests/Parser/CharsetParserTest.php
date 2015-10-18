<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CharsetParser;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CharsetParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new CharsetParser(new DomCrawlerFactory);
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
    <meta charset="UTF-8" />
</head>
<body>
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
        $this->assertTrue($r->has('charset'));
        $this->assertSame('UTF-8', $r->get('charset'));

        $return = $this->p->parse(
            $r = new Resource('', 'text/html; charset="UTF-16"'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('charset'));
        $this->assertSame('UTF-16', $r->get('charset'));
    }

    public function testName()
    {
        $this->assertSame('charset', CharsetParser::getName());
    }
}
