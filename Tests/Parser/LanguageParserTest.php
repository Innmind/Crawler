<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\LanguageParser;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class LanguageParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new LanguageParser;

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('languages'));

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
    <article></article>
</body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('languages'));
    }

    public function testParse()
    {
        $p = new LanguageParser;

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(
                200,
                ['Content-Language' => 'fr, en, d3o']
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('languages'));
        $this->assertSame(['fr', 'en'], $r->get('languages'));

        $return = $p->parse(
            $r = new Resource('', 'text/html'),
            new Response(
                200,
                [],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html lang="de">
    <head>
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
        $this->assertTrue($r->has('languages'));
        $this->assertSame(['de'], $r->get('languages'));

        $return = $p->parse(
            $r = new Resource('', 'text/html'),
            new Response(
                200,
                [],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Language" content="uk, us" />
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
        $this->assertTrue($r->has('languages'));
        $this->assertSame(['uk', 'us'], $r->get('languages'));
    }

    public function testName()
    {
        $this->assertSame('languages', LanguageParser::getName());
    }
}
