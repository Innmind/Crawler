<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\ThemeColorParser;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class ThemeColorParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new ThemeColorParser;

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
        $p = new ThemeColorParser;
        $response = new Response(
            200,
            [],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta name="theme-color" content="#3399FF" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $r = new Resource('', 'text/html');
        $return = $p->parse($r, $response, new Stopwatch);

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('theme-color'));
        $this->assertSame([210.0, 100.0, 60.0], $r->get('theme-color'));

        $response = new Response(
            200,
            [],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta name="theme-color" content="#088" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $p->parse($r, $response, new Stopwatch);

        $this->assertSame([180.0, 100.0, 26.7], $r->get('theme-color'));

        $response = new Response(
            200,
            [],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta name="theme-color" content="rgb(152, 125, 62)" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $p->parse($r, $response, new Stopwatch);

        $this->assertSame([42.0, 42.1, 42.0], $r->get('theme-color'));

        $response = new Response(
            200,
            [],
            Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta name="theme-color" content="hsl(24.1, 66.1%, 42.1%)" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $p->parse($r, $response, new Stopwatch);

        $this->assertSame([24.1, 66.1, 42.1], $r->get('theme-color'));
    }

    public function testName()
    {
        $this->assertSame('theme-color', ThemeColorParser::getName());
    }
}
