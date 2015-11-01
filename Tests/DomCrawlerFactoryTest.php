<?php

namespace Innmind\Crawler\Tests;

use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\Response;

class DomCrawlerFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $f;

    public function setUp()
    {
        $this->f = new DomCrawlerFactory;
    }

    public function testMake()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'text/html']
        );

        $c = $this->f->make($response);

        $this->assertInstanceOf(Crawler::class, $c);
        $this->assertSame($c, $this->f->make($response));
    }

    public function testRelease()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'text/html']
        );

        $c = $this->f->make($response);

        $this->assertSame($this->f, $this->f->release($response));
        $this->assertNotSame($c, $this->f->make($response));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A DomCrawler can only be created for html content
     */
    public function testThrowIfResponseIsNotHtml()
    {
        $this->f->make(new Response(200, ['Content-Type' => 'application/json']));
    }
}
