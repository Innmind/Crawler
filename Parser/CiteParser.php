<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Message\ResponseInterface;

class CiteParser implements ParserInterface
{
    protected $crawlerFactory;

    public function __construct(DomCrawlerFactory $crawlerFactory)
    {
        $this->crawlerFactory = $crawlerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(
        HttpResource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = $this->crawlerFactory->make($response);
        $citations = $dom
            ->filter('cite')
            ->each(function(Crawler $node) {
                return $node->text();
            });

        if (!empty($citations)) {
            $resource->set('citations', $citations);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'cite';
    }
}
