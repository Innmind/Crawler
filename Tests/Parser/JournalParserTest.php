<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\JournalParser;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class JournalParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new JournalParser;

        $return = $p->parse(
            $r = new Resource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertFalse($r->has('journal'));

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
        $this->assertFalse($r->has('journal'));
    }

    public function testParse()
    {
        $p = new JournalParser;

        $return = $p->parse(
            $r = new Resource('', 'text/html'),
            new Response(
                200,
                [],
                Stream::factory(<<<HTML
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <article></article>
        <article></article>
    </body>
</html>
HTML
                )
            ),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('journal'));
        $this->assertTrue($r->get('journal'));
    }

    public function testName()
    {
        $this->assertSame('journal', JournalParser::getName());
    }
}
