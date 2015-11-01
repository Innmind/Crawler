<?php

namespace Innmind\Crawler\Tests\Parser;

use Innmind\Crawler\Parser\ContentParser;
use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\DomCrawlerFactory;
use Innmind\Crawler\HttpResource;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class ContentParserTest extends \PHPUnit_Framework_TestCase
{
    protected $p;
    protected $dcf;

    public function setUp()
    {
        $this->p = new ContentParser(
            $this->dcf = new DomCrawlerFactory
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(ParserInterface::class, $this->p);
    }

    public function testDoesnParse()
    {
        $return = $this->p->parse(
            $r = new HttpResource('', 'application/json'),
            new Response(200),
            new Stopwatch
        );

        $this->assertSame($return, $r);
        $this->assertSame([], $r->keys());
    }

    /**
     * @dataProvider fixtures
     */
    public function testParse($file, $expected)
    {
        $return = $this->p->parse(
            $r = new HttpResource(
                'https://en.wikipedia.org/wiki/The_Hitchhiker%27s_Guide_to_the_Galaxy',
                'text/html'
            ),
            $response = new Response(
                200,
                ['Content-Type' => 'text/html'],
                Stream::factory(file_get_contents($file))
            ),
            new StopWatch
        );

        $dc = $this->dcf->make($response);

        $this->assertSame($r, $return);
        $this->assertSame(
            trim($dc->filter($expected)->text()),
            $r->get('content')
        );
    }

    public function fixtures()
    {
        return [
            [
                'fixtures/medium.html',
                'body main',
            ],
            [
                'fixtures/h2g2.html',
                '#content',
            ],
            [
                'fixtures/reddit.html',
                'body > .content',
            ],
            [
                'fixtures/lemonde.html',
                '.global',
            ],
        ];
    }
}
