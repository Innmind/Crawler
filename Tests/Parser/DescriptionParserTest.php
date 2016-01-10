<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\DescriptionParser;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class DescriptionParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new DescriptionParser(new DomCrawlerFactory);
    }

    public function testDoesntParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
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
    <meta name="description" content="foo" />
</head>
<body>
</body>
</html>
HTML
            )
        );

        $return = $this->p->parse(
            $r = new HttpResource('', 'text/html'),
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('description'));
        $this->assertSame('foo', $r->get('description'));

        $response = new Response(
            200,
            ['Content-Type' => 'text/html']
        );
        $r = new HttpResource('', 'text/html');
        $r->set('content', "Lorem  ipsum dolor     sit amet,     consectetur \t adipiscing elit. Vestibulum nulla ex, placerat vitae turpis ut, cursus varius sem. Aenean in feugiat diam, at varius sapien. Aliquam interdum ex et eleifend pellentesque. In et lacus diam. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse bibendum a justo nec ornare. Maecenas vitae purus finibus, interdum metus eget, porttitor tortor. Aenean imperdiet vel metus nec sagittis. Donec blandit justo finibus ligula tempor rutrum. Morbi id accumsan arcu. Cras sollicitudin, arcu eu mattis blandit, risus dolor tempus arcu, ut dignissim elit orci et velit. Phasellus eget elementum est, nec iaculis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris vehicula odio id felis convallis dictum. Suspendisse at libero nisl.");

        $return = $this->p->parse(
            $r,
            $response,
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertTrue($r->has('description'));
        $this->assertSame('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nulla ex, placerat vitae turpis ut, cursus varius sem. Aenean in feugiat diam, at ...', $r->get('description'));
    }

    public function testName()
    {
        $this->assertSame('description', DescriptionParser::getName());
    }
}
