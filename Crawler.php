<?php

namespace Innmind\Crawler;

use Innmind\Crawler\Event\PreRequestEvent;
use Innmind\Crawler\Event\PostRequestEvent;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use GuzzleHttp\Client as Http;
use GuzzleHttp\Message\Request as HttpRequest;

class Crawler implements CrawlerInterface
{
    const USER_AGENT = 'Innmind Crawler 1.0';

    protected $http;
    protected $parser;
    protected $dispatcher;
    protected $resources;

    public function __construct(
        Http $http,
        ParserInterface $parser,
        EventDispatcherInterface $dispatcher
    ) {
        $this->http = $http;
        $this->parser = $parser;
        $this->dispatcher = $dispatcher;
        $this->resources = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function crawl(Request $request)
    {
        $httpRequest = new HttpRequest(
            'GET',
            $request->getUrl(),
            array_merge(
                ['User-Agent' => self::USER_AGENT],
                $request->getHeaders()
            )
        );
        $stopwath = new Stopwatch;

        $stopwath->start('crawl');
        $this->dispatcher->dispatch(
            Events::PRE_REQUEST,
            new PreRequestEvent($httpRequest)
        );
        $response = $this->http->send($httpRequest);
        $this->dispatcher->dispatch(
            Events::POST_REQUEST,
            new PostRequestEvent($httpRequest, $response)
        );
        $stopwath->stop('crawl');

        $resource = new Resource(
            $response->getEffectiveUrl(),
            $response->getHeader('Content-Type')
        );

        $stopwath->openSection();
        $this->parser->parse($resource, $response, $stopwath);
        $stopwath->stopSection('parsing');

        $this->resources->attach($resource, $stopwath);

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getStopwatch(Resource $resource)
    {
        if (!$this->resources->contains($resource)) {
            throw new \InvalidArgumentException(sprintf(
                'No references found for the resource at "%s"',
                $resource->getUrl()
            ));
        }

        return $this->resources[$resource];
    }

    /**
     * {@inheritdoc}
     */
    public function release(Resource $resource)
    {
        $this->resources->detach($resource);

        return $this;
    }
}
