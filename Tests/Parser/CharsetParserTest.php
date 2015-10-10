<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\CharsetParser;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CharsetParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new CharsetParser;

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
        $p = new CharsetParser;
        $response = new Response(
            200,
            [],
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

        $return = $p->parse(
            $r = new Resource('', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('charset'));
        $this->assertSame('UTF-8', $r->get('charset'));

        $return = $p->parse(
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
