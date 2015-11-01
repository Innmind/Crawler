<?php

namespace Innmind\Crawler\Tests;

use Innmind\Crawler\Crawler;
use Innmind\Crawler\Parser;
use Innmind\Crawler\Request;
use Innmind\Crawler\HttpResource;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Client as Http;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\RequestInterface;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    protected $c;
    protected $d;

    public function setUp()
    {
        $http = $this
            ->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $http
            ->method('send')
            ->will($this->returnCallback(function($request) {
                $response = new Response(200);
                $response->setEffectiveUrl($request->getUrl());

                return $response;
            }));

        $this->c = new Crawler(
            $http,
            new Parser,
            $this->d = new EventDispatcher
        );
    }

    public function testCrawl()
    {
        $preFired = false;
        $postFired = false;
        $this->d->addListener(
            'innmind.crawler.pre_request',
            function($event) use (&$preFired) {
                $this->assertInstanceOf(
                    RequestInterface::class,
                    $event->getRequest()
                );
                $this->assertSame(
                    'Innmind Crawler 1.0',
                    $event->getRequest()->getHeader('User-Agent')
                );
                $preFired = true;
            });
        $this->d->addListener(
            'innmind.crawler.post_request',
            function($event) use (&$postFired) {
                $this->assertInstanceOf(
                    RequestInterface::class,
                    $event->getRequest()
                );
                $this->assertInstanceOf(
                    ResponseInterface::class,
                    $event->getResponse()
                );
                $postFired = true;
            });

        $request = new Request('http://example.com/');
        $resource = $this->c->crawl($request);
        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('http://example.com/', $resource->getUrl());
        $this->assertTrue($preFired);
        $this->assertTrue($postFired);
        $stopwatch = $this->c->getStopwatch($resource);
        $this->assertInstanceOf(Stopwatch::class, $stopwatch);
        $this->assertSame(2, count($stopwatch->getSections()));

        $this->assertSame($this->c, $this->c->release($resource));
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'No references found for the resource at "http://example.com/"'
        );
        $this->c->getStopwatch($resource);
    }
}
