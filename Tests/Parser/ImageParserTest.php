<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\ImageParser;
use Innmind\Crawler\Resource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;

class ImageParserTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesntParse()
    {
        $p = new ImageParser;

        $return = $p->parse(
            $r = new Resource('', 'text/html'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($r, $return);
        $this->assertSame([], $r->keys());
    }

    public function testParse()
    {
        $p = new ImageParser;

        $resource = new Resource('fixtures/dont_panic.jpg', 'image/jpeg');
        $response = new Response(200);

        $return = $p->parse($resource, $response, new Stopwatch);

        $this->assertSame($resource, $return);
        $this->assertSame(604, $resource->get('width'));
        $this->assertSame(800, $resource->get('height'));
        $this->assertSame('image/jpeg', $resource->get('mime'));
        $this->assertSame('.jpeg', $resource->get('extension'));
        $this->assertTrue($resource->has('exif'));
        $this->assertSame(54636, $resource->get('weight'));
    }

    public function testName()
    {
        $this->assertSame('image', ImageParser::getName());
    }
}
