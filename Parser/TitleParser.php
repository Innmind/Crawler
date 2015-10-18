<?php

namespace Innmind\Crawler\Parser;

use Innmind\Crawler\ParserInterface;
use Innmind\Crawler\Resource;
use Innmind\Crawler\DomCrawlerFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Message\ResponseInterface;

class TitleParser implements ParserInterface
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
        Resource $resource,
        ResponseInterface $response,
        Stopwatch $stopwatch
    ) {
        if (!preg_match('/html/', $resource->getContentType())) {
            return $resource;
        }

        $dom = $this->crawlerFactory->make($response);
        $h1 = $dom->filter('h1');
        $title = $dom->filter('head title');

        if ($h1->count() === 1 && trim($h1->text()) !== '') {
            $resource->set('title', trim($h1->text()));
        } else if ($title->count() === 1 && trim($title->text()) !== '') {
            $resource->set('title', trim($title->text()));
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'title';
    }
}
